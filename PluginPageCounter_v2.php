<?php
class PluginPageCounter_v2{
  public $data = null;
  public $mysql = null;
  function __construct($buto) {
    if($buto){
      /**
       * Enable.
       */
      wfPlugin::enable('davegandy/fontawesome450');
      wfPlugin::enable('wf/dom');
      wfPlugin::enable('wf/bootstrap');
      wfPlugin::enable('wf/ajax');
      wfPlugin::enable('samstephenson/prototype');
      wfPlugin::enable('wf/textareatab');
      wfPlugin::enable('wf/callbackjson');
      wfPlugin::enable('prism/prismjs');
      wfPlugin::enable('wf/onkeypress');
      wfPlugin::enable('wf/bootstrapjs');
      wfPlugin::enable('datatable/datatable_1_10_16');
      wfPlugin::enable('eternicode/bootstrapdatepicker');
      wfPlugin::enable('twitter/bootstrap335v');
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
    if(wfArray::get($GLOBALS, 'sys/plugin') != 'page/counter_v2'){
      $post_data = wfHelp::getYmlDump(wfRequest::getAll());
      $post_data = str_replace("'", "\'", $post_data);
      $this->db_open();
      wfPlugin::includeonce('wf/array');
      $server = new PluginWfArray($_SERVER);
      $REQUEST_URI = $server->get('REQUEST_URI');
      $REQUEST_URI = utf8_encode($REQUEST_URI);
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
    wfPlugin::includeonce('wf/array');
    wfPlugin::includeonce('wf/yml');
    wfPlugin::enable('datatable/datatable_1_10_13');
    wfPlugin::enable('datatable/datatable_1_10_16');
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/page/counter_v2/layout');
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
    $this->db_open();
    $rs = $this->mysql->runSql("select session_id, HTTP_HOST, HTTP_USER_AGENT, HTTP_COOKIE, REMOTE_ADDR, HTTP_REFERER, REQUEST_URI, theme, language, created_at, REQUEST_METHOD from page_counter_v2_page order by created_at desc limit ".$this->data->get('settings/list_all/limit').";");
    $rs = $rs['data'];
    $tr = array();
    foreach ($rs as $key => $value){
      $item = new PluginWfArray($value);
      $tr[] = wfDocument::createHtmlElement('tr', array(
          wfDocument::createHtmlElement('td', date('ymd H:i', strtotime($item->get('created_at')))),
          wfDocument::createHtmlElement('td', $item->get('session_id')),
          wfDocument::createHtmlElement('td', $item->get('HTTP_HOST')),
          wfDocument::createHtmlElement('td', $item->get('HTTP_USER_AGENT')),
          wfDocument::createHtmlElement('td', $item->get('HTTP_COOKIE')),
          wfDocument::createHtmlElement('td', array($this->getRemoteAddrLink($item->get('REMOTE_ADDR')))),
          wfDocument::createHtmlElement('td', $item->get('HTTP_REFERER')),
          wfDocument::createHtmlElement('td', $item->get('REQUEST_URI')),
          wfDocument::createHtmlElement('td', $item->get('theme')),
          wfDocument::createHtmlElement('td', $item->get('language')),
          wfDocument::createHtmlElement('td', $item->get('REQUEST_METHOD'))
          ));
    }
    $page = $this->getYml('page/list_all.yml');
    $page->setById('tbody', 'innerHTML', $tr);
    wfDocument::mergeLayout($page->get());
  }
  private function getRemoteAddrLink($REMOTE_ADDR){
    return wfDocument::createHtmlElement('a', $REMOTE_ADDR, array('href' => "http://whatismyipaddress.com/ip/$REMOTE_ADDR", 'target' => '_blank'));
  }
  public function page_list_group_by_ip(){
    $this->init_page();
    $this->db_open();
    $rs = $this->mysql->runSql("select REMOTE_ADDR, count(session_id) as hits from page_counter_v2_page group by REMOTE_ADDR;");
    $rs = $rs['data'];
    $tr = array();
    foreach ($rs as $key => $value){
      $item = new PluginWfArray($value);
      $tr[] = wfDocument::createHtmlElement('tr', array(
          wfDocument::createHtmlElement('td', array($this->getRemoteAddrLink($item->get('REMOTE_ADDR')))),
          wfDocument::createHtmlElement('td', $item->get('hits'))
          ));
    }
    $page = $this->getYml('page/list_group_by_ip.yml');
    $page->setById('tbody', 'innerHTML', $tr);
    wfDocument::mergeLayout($page->get());
  }
  public function page_list_group_by_page(){
    $this->init_page();
    $this->db_open();
    $rs = $this->mysql->runSql("select class, method, count(session_id) as hits from page_counter_v2_page group by class, method;");
    $rs = $rs['data'];
    $tr = array();
    foreach ($rs as $key => $value){
      $item = new PluginWfArray($value);
      $tr[] = wfDocument::createHtmlElement('tr', array(
          wfDocument::createHtmlElement('td', $item->get('class')),
          wfDocument::createHtmlElement('td', $item->get('method')),
          wfDocument::createHtmlElement('td', $item->get('hits'))
          ));
    }
    $page = $this->getYml('page/list_group_by_page.yml');
    $page->setById('tbody', 'innerHTML', $tr);
    wfDocument::mergeLayout($page->get());
  }
  public function page_list_group_by_day(){
    $this->init_page();
    $this->db_open();
    $rs = $this->mysql->runSql("select substr(created_at, 1, 10) as day, count(session_id) as hits from page_counter_v2_page group by day;");
    $rs = $rs['data'];
    $tr = array();
    foreach ($rs as $key => $value){
      $item = new PluginWfArray($value);
      $tr[] = wfDocument::createHtmlElement('tr', array(
          wfDocument::createHtmlElement('td', $item->get('day')),
          wfDocument::createHtmlElement('td', $item->get('hits'))
          ));
    }
    $page = $this->getYml('page/list_group_by_day.yml');
    $page->setById('tbody', 'innerHTML', $tr);
    wfDocument::mergeLayout($page->get());
  }
  public function page_list_group_by_day_and_ip(){
    $this->init_page();
    $this->db_open();
    $rs = $this->mysql->runSql("select substr(created_at, 1, 10) as day, REMOTE_ADDR, count(session_id) as hits from page_counter_v2_page group by day, REMOTE_ADDR;");
    $rs = $rs['data'];
    $tr = array();
    foreach ($rs as $key => $value){
      $item = new PluginWfArray($value);
      $tr[] = wfDocument::createHtmlElement('tr', array(
          wfDocument::createHtmlElement('td', $item->get('day')),
          wfDocument::createHtmlElement('td', array($this->getRemoteAddrLink($item->get('REMOTE_ADDR')))),
          wfDocument::createHtmlElement('td', $item->get('hits'))
          ));
    }
    $page = $this->getYml('page/list_group_by_day_and_ip.yml');
    $page->setById('tbody', 'innerHTML', $tr);
    wfDocument::mergeLayout($page->get());
  }
  private function getYml($file){
    return new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/page/counter_v2/'.$file);
  }
}
