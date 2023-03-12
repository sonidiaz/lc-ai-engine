<?
class LC_DataSet {
  private static $api_host = 'https://lacabrera.eco/';
  private static $requestArgs = array(
    'headers' => array(
       'Accept' => 'application/json',
       'timeout' => 30
    ),
 );

  static function getWpDataPage($page) {
    $response = wp_remote_get(self::$api_host.'wp-json/wp/v2/pages/'.$page, self::$requestArgs);
    
    if ( is_array( $response ) && ! is_wp_error( $response ) ) {
      $body = wp_remote_retrieve_body( $response );
      $data = json_decode( $body );
      return $data;
    }
  }
  static function getWpDataPages() {
    
    $response = wp_remote_get(self::$api_host.'wp-json/wp/v2/pages/', self::$requestArgs);
    
    if ( is_array( $response ) && ! is_wp_error( $response ) ) {
      $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );
        return $data;
      }
  }

  public function lc_endpoint_pages() {
    function lc_endpoint_init() {
      register_rest_route( 'lc/v1', '/pages/', array(
        'methods' => 'GET',
        'callback' => 'getWpPagesContent'
      ) );
      register_rest_route( 'lc/v1', '/page/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'getWpPageContent',
      ) );
    }
    
    function getWpPageContent($data) {
      $dataRemote = LC_DataSet::getWpDataPage($data['id']);
      $dataTemp = array();
      $contentCleaner = '';
      if($dataRemote->content->rendered !== '') {
        $contentCleaner = LC_DataSet::cleanContent($dataRemote->content->rendered);
      }
      $respGPT = wp_remote_post('https://api.openai.com/v1/completions', array(
        'method'      => 'POST',
        'headers' => array(
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer '
       ),
        'body' => json_encode(array(
          'model' => "text-davinci-003",
          "prompt" => $contentCleaner,
          "temperature" => 0.8,
          "max_tokens" => 1700
        )),
      ));
      $prevText = json_decode($respGPT['body'])->choices[0]->text;
      $contentGPT = preg_replace('/[\n\t]/', ' ', $prevText);


      array_push($dataTemp, array(
        "id" => $dataRemote->id, 
        "title" => $dataRemote->title->rendered, 
        "yoast_title" => $dataRemote->yoast_head_json->title,
        "yoast_description" => $dataRemote->yoast_head_json->description,
        "content" => $contentCleaner,

      ));
      return rest_ensure_response($dataTemp); 

    }

    function getWpPagesContent() {
      
      $dataRemote = LC_DataSet::getWpDataPages();
      $dataTemp = array();
      
      

      foreach ($dataRemote as $key => $value) {
        $contentCleaner = '';
        if($value->content->rendered !== '') {
          $contentCleaner = LC_DataSet::cleanContent($value->content->rendered);
        }

        array_push($dataTemp, array(
          "id" => $value->id, 
          "title" => $value->title->rendered, 
          "yoast_title" => $value->yoast_head_json->title,
          "yoast_description" => $value->yoast_head_json->description,
          "content" => $contentCleaner,
  
        ));
      }

      return rest_ensure_response( $dataTemp );
    }
    add_action( 'rest_api_init', 'lc_endpoint_init' );
  }
  static function cleanContent($textstring) {
    /* $hideBlancSpac2 = preg_replace('/document\.addEventListener\(\"DOMContentLoaded\".*?\}\)', '', $hideBlancSpac);*/
    $contentText = preg_replace("/<.*?>/", "", $textstring);
    $hideBlancSpac = preg_replace('/[\n\t]/', '', $contentText);
    return $hideBlancSpac;
  }

}