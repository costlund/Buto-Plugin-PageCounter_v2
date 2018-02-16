<?php
/**
 * Writes page hits to MySql table.
 * 
 * Backend settings.
plugin_modules:
  counter:
    plugin: 'page/counter_v2'
    settings:
      mysql: 'yml:/_php_settings_.yml'
 * 
 * Event settings.
plugin:
  page:
    counter_v2:
      settings:
        mysql: 'yml:/_php_settings_.yml'
events:
  document_render_before:
    -
      plugin: 'page/counter_v2'
      method: count
 */
class PluginPageCounter_v2{
  public $data = null;
  public $mysql = null;
  function __construct($buto) {
    if($buto){
      wfPlugin::includeonce('wf/array');
      wfPlugin::includeonce('wf/yml');
      $this->data = wfPlugin::getPluginSettings('page/counter_v2', true);
    }
  }
  public function db_open(){
    wfPlugin::includeonce('wf/mysql');
    $this->mysql = new PluginWfMysql();
    $this->mysql->open($this->data->get('settings/mysql'));
  }
  /**
   * Run this event on document_render_before.
   */
  public function event_count($data){
    if(wfArray::get($GLOBALS, 'sys/plugin') != 'page/counter_v2'){
      $this->db_open();
      wfPlugin::includeonce('wf/array');
      $server = new PluginWfArray($_SERVER);
      $this->mysql->runSql("insert into page_counter_v2_page (session_id,HTTP_HOST,HTTP_USER_AGENT,HTTP_REFERER,HTTP_COOKIE,REMOTE_ADDR,REQUEST_URI,theme,class,method,language) values ('".session_id()."','".$server->get('HTTP_HOST')."','".$server->get('HTTP_USER_AGENT')."','".$server->get('HTTP_REFERER')."','".$server->get('HTTP_COOKIE')."','".$server->get('REMOTE_ADDR')."','".$server->get('REQUEST_URI')."','".wfArray::get($GLOBALS, 'sys/theme')."','".wfArray::get($GLOBALS, 'sys/class')."','".wfArray::get($GLOBALS, 'sys/method')."','".wfI18n::getLanguage()."')");
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
    if(!wfUser::hasRole("webmaster") && !wfUser::hasRole("databasemaster") && !wfUser::hasRole("webadmin")){
      exit('Role webmaster, webadmin or databasemaster is required!');
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
    $rs = $this->mysql->runSql("select session_id, HTTP_HOST, HTTP_USER_AGENT, HTTP_COOKIE, REMOTE_ADDR, HTTP_REFERER, REQUEST_URI, theme, class, method, language, created_at from page_counter_v2_page order by created_at desc;");
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
          wfDocument::createHtmlElement('td', $item->get('class')),
          wfDocument::createHtmlElement('td', $item->get('method')),
          wfDocument::createHtmlElement('td', $item->get('language'))
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
