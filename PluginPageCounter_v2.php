<?php
class PluginPageCounter_v2{
  public $data = null;
  public $mysql = null;
  function __construct($buto) {
    if($buto){
      /**
       * 
       */
      wfPlugin::includeonce('wf/array');
      wfPlugin::includeonce('wf/yml');
      $this->data = wfPlugin::getPluginSettings('page/counter_v2', true);
      if(!$this->data->get('settings/list_all/limit')){
        $this->data->set('settings/list_all/limit', 1000);
      }
      /**
       * 
       */
      set_time_limit(60*10);
      ini_set('memory_limit', '2048M');
    }
  }
  public function db_open(){
    wfPlugin::includeonce('wf/mysql');
    $this->mysql = new PluginWfMysql();
    /**
     * Skip log.
     */
    $this->mysql->event = false;
    /**
     * 
     */
    $this->mysql->open($this->data->get('settings/mysql'));
  }
  /**
   * Run this event on document_render_before.
   */
  public function event_count($data){
    $data = new PluginWfArray($data);
    wfPlugin::includeonce('wf/array');
    $server = new PluginWfArray($_SERVER);
    $REQUEST_URI = $server->get('REQUEST_URI');
    $REQUEST_URI = mb_convert_encoding($REQUEST_URI, 'UTF-8', 'ISO-8859-1');
    /**
     * filter, class
     */
    $filter_class = false;
    if($data->get('data/filter/class') && in_array(wfArray::get($GLOBALS, 'sys/class'), $data->get('data/filter/class'))){
      $filter_class = true;
    }
    /**
     * filter, uri
     */
    $filter_uri = false;
    if( $data->get('data/filter/uri') ){
      wfPlugin::includeonce('string/match');
      $match = new PluginStringMatch();
      foreach($data->get('data/filter/uri') as $k => $v){
        if($match->wildcard($v, $REQUEST_URI) > 0){
          $filter_uri = true;
          break;
        }
      }
    }
    /**
     * 
     */
    if(wfArray::get($GLOBALS, 'sys/plugin') != 'page/counter_v2' && !$filter_class && !$filter_uri){
      $post_data = wfHelp::getYmlDump(wfRequest::getAll());
      $post_data = wfPhpfunc::str_replace("'", "\'", $post_data);
      $this->db_open();
      wfPlugin::includeonce('wf/array');
      $server = new PluginWfArray($_SERVER);
      $REQUEST_URI = $server->get('REQUEST_URI');
      $REQUEST_URI = mb_convert_encoding($REQUEST_URI, 'UTF-8', 'ISO-8859-1');
      $REQUEST_METHOD = $server->get('REQUEST_METHOD');
      $sql = new PluginWfArray();
      $sql->set('sql', "insert into page_counter_v2_page (session_id,HTTP_HOST,HTTP_USER_AGENT,HTTP_REFERER,HTTP_COOKIE,REMOTE_ADDR,REQUEST_URI,theme,class,method,language,REQUEST_METHOD,post_data) values (?,?,?,?,?,?,?,?,?,?,?,?,?)");
      $sql->set('params/0', array('type' => 's', 'value' => session_id()));
      $sql->set('params/1', array('type' => 's', 'value' => $server->get('HTTP_HOST')));
      $sql->set('params/2', array('type' => 's', 'value' => $server->get('HTTP_USER_AGENT')));
      $sql->set('params/3', array('type' => 's', 'value' => $server->get('HTTP_REFERER')));
      $sql->set('params/4', array('type' => 's', 'value' => $server->get('HTTP_COOKIE')));
      $sql->set('params/5', array('type' => 's', 'value' => $server->get('REMOTE_ADDR')));
      $sql->set('params/6', array('type' => 's', 'value' => $REQUEST_URI));
      $sql->set('params/7', array('type' => 's', 'value' => wfArray::get($GLOBALS, 'sys/theme')));
      $sql->set('params/8', array('type' => 's', 'value' => wfArray::get($GLOBALS, 'sys/class')));
      $sql->set('params/9', array('type' => 's', 'value' => wfArray::get($GLOBALS, 'sys/method')));
      $sql->set('params/10', array('type' => 's', 'value' => wfI18n::getLanguage()));
      $sql->set('params/11', array('type' => 's', 'value' => $REQUEST_METHOD));
      $sql->set('params/12', array('type' => 's', 'value' => $post_data));
      $this->mysql->execute($sql->get());
    }
    return null;
  }
  /**
   * 
   */
  private function init_page(){
    wfPlugin::enable('wf/table');
    wfPlugin::includeonce('wf/array');
    wfPlugin::includeonce('wf/yml');
    wfPlugin::enable('datatable/datatable_1_10_18');
    wfGlobals::setSys('layout_path', '/plugin/page/counter_v2/layout');
    if(!wfUser::hasRole("webmaster") && !wfUser::hasRole("webadmin")){
      exit('Role webmaster or webadmin is required!');
    }
  }
  /**
   * Start page.
   */
  public function page_start(){
    $this->init_page();
    $page = $this->getYml('page/start.yml');
    wfDocument::mergeLayout($page->get());
  }
  public function page_list_all(){
    $this->init_page();
    $page = $this->getYml('page/list_all.yml');
    wfDocument::mergeLayout($page->get());
  }
  public function page_list_all_data(){
    $this->db_open();
    $rs = $this->mysql->runSql("select session_id, HTTP_HOST, HTTP_USER_AGENT, HTTP_COOKIE, REMOTE_ADDR, HTTP_REFERER, REQUEST_URI, theme, language, created_at, REQUEST_METHOD from page_counter_v2_page order by created_at desc limit ".$this->data->get('settings/list_all/limit').";");
    $rs = $rs['data'];
    wfPlugin::includeonce('datatable/datatable_1_10_18');
    $datatable = new PluginDatatableDatatable_1_10_18();
    exit($datatable->set_table_data($rs));
  }
  private function getRemoteAddrLink($REMOTE_ADDR){
    return wfDocument::createHtmlElement('a', $REMOTE_ADDR, array('href' => "http://whatismyipaddress.com/ip/$REMOTE_ADDR", 'target' => '_blank'));
  }
  public function page_list_group_by_ip(){
    $this->init_page();
    $page = $this->getYml('page/list_group_by_ip.yml');
    wfDocument::mergeLayout($page->get());
  }
  public function page_list_group_by_ip_data(){
    $this->db_open();
    $rs = $this->mysql->runSql("select REMOTE_ADDR, count(session_id) as hits from page_counter_v2_page group by REMOTE_ADDR;");
    $rs = $rs['data'];
    wfPlugin::includeonce('datatable/datatable_1_10_18');
    $datatable = new PluginDatatableDatatable_1_10_18();
    exit($datatable->set_table_data($rs));
  }
  public function page_list_group_by_page(){
    $this->init_page();
    $page = $this->getYml('page/list_group_by_page.yml');
    wfDocument::mergeLayout($page->get());
  }
  public function page_list_group_by_page_data(){
    $this->db_open();
    $rs = $this->mysql->runSql("select class, method, count(session_id) as hits from page_counter_v2_page group by class, method;");
    $rs = $rs['data'];
    wfPlugin::includeonce('datatable/datatable_1_10_18');
    $datatable = new PluginDatatableDatatable_1_10_18();
    exit($datatable->set_table_data($rs));
  }
  public function page_list_group_by_day(){
    $this->init_page();
    $page = $this->getYml('page/list_group_by_day.yml');
    wfDocument::mergeLayout($page->get());
  }
  public function page_list_group_by_day_data(){
    $this->db_open();
    $rs = $this->mysql->runSql("select left(created_at,10) as day, count(session_id) as hits from page_counter_v2_page group by day;");
    $rs = $rs['data'];
    wfPlugin::includeonce('datatable/datatable_1_10_18');
    $datatable = new PluginDatatableDatatable_1_10_18();
    exit($datatable->set_table_data($rs));
  }
  public function page_list_group_by_day_and_ip(){
    $this->init_page();
    $page = $this->getYml('page/list_group_by_day_and_ip.yml');
    wfDocument::mergeLayout($page->get());
  }
  public function page_list_group_by_day_and_ip_data(){
    $this->db_open();
    $rs = $this->mysql->runSql("select left(created_at,10) as day, REMOTE_ADDR, count(session_id) as hits from page_counter_v2_page group by day, REMOTE_ADDR;");
    $rs = $rs['data'];
    wfPlugin::includeonce('datatable/datatable_1_10_18');
    $datatable = new PluginDatatableDatatable_1_10_18();
    exit($datatable->set_table_data($rs));
  }
  private function getYml($file){
    return new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/page/counter_v2/'.$file);
  }
}
