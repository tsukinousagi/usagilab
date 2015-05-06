<?php

class Anime_db_model extends CI_Model {

    //快取設定
    public $cache_dir = 'cache/';
    public $cache_expire = 86400;


    function __construct() {
        parent::__construct();
        $this->load->library('simple_html_dom');
    }


    public function test() {
        return '123';
    }

    public function flush_test() {
        for($i = 0; $i <= rand(10,100); $i++) {
            echo(rand(0,9999) . PHP_EOL);
            @ob_flush();
            flush();
//            sleep(rand(1,3));
        }
        echo('Done!');
    }

    public function fetch_title() {
        //限制取得筆數
        $limit = 1;

        //cal.syoboi.jp節目清單分類參數
        $syoboi_jp_category = array(1,10,7,4,8);
        foreach ($syoboi_jp_category as $c) {
            //取得所有動畫標題清單
            $src = $this->get_page(sprintf('http://cal.syoboi.jp/list?cat=%d', $c));
            $titles = $this->get_all_titles($src);
            foreach($titles as $t) {
                $this->msg(sprintf('處理項目: %s(%d)', $t['title'], $t['id']));
                //先查db看這作品是不是需要再處理
                $title_exists = FALSE;
                if (!$title_exists) {
                    $src = $this->get_page(sprintf('http://cal.syoboi.jp/tid/%d', $t['id']));

                    //取得分類(雖然暫時用不到)
                    $category = $this->get_title_category($src);

                    //找出此系列的第一個作品
                    $first = $this->get_first_in_series($src);
                    if ($first) {
                        $this->msg(sprintf('此系列第一個作品ID: %d', $first));
                    } else {
                        $this->msg('此系列目前只有一個作品');
                    }
                    //去維基百科查中文標題
                    $wikipedia_link = $this->get_title_wikipedia_link($src);
                    if ($wikipedia_link <> '') {
                        $src = $this->get_page($wikipedia_link);
                        $link_zh = $this->get_zh_link_from_wikipedia($src);
                        if ($link_zh <> '') {
                            //強制使用繁體中文的維基百科頁面
                            $link_zh = str_replace('/wiki/', '/zh-tw/', $link_zh);
                            $src = $this->get_page('http:' . $link_zh);
                            $title_zh = $this->get_zh_title_from_wikipedia($src);
                            $this->msg(sprintf('作品中文標題為: %s', $title_zh));
                        } else {
                            $this->msg('找不到此作品的中文標題');
                            $title_zh = '';
                        }
                    }
                    //寫入資料庫
//                    $sql = sprintf("INSERT INTO `anime_title` (
//                                    `title_jp`, `title_zh`, `syoboi_jp_id`, `parent_syoboi_jp_id`, `update_flag`
//                                    ) VALUES (
//                                    '%s', '%s', %d, %d, %d
                    exit;
                }
            }
        }

    }

    //從cal.syoboi.jp取得所有動畫節目標題
    public function get_all_titles($src) {
        $result = array();
        $html = str_get_html($src);
        foreach($html->find('table.TitleList tbody tr td a') as $titles) {
            if (preg_match('/[0-9]+/', $titles->href, $matches)) {
                $title = array('id' => $matches[0], 'title' => $titles->plaintext);
                $result[] = $title;
            }
        }
        return $result;
    }

    //從cal.syoboi.jp取得某節目的分類
    public function get_title_category($src) {
        $result = array();
        $html = str_get_html($src);
        $link = $html->find('a[rel=contents]', 0)->href;
        if (preg_match('/[0-9]+/', $link, $matches)) {
            return $matches[0];
        } else {
            return FALSE;
        }
    }

    //從cal.syoboi.jp取得某作品系列的第一個作品
    public function get_first_in_series($src) {
        $result = array();
        $html = str_get_html($src);
        foreach($html->find('div.tidGroup', 1)->find('ul.tidList li') as $titles) {
            $tlink = $titles->find('a');
            if (sizeof($tlink) > 0) {
                foreach($tlink as $v) {
                    $link = $v->href;
                    if (preg_match('/[0-9]+/', $link, $matches)) {
                        $first_id = $matches[0];
                    } else {
                        $first_id = FALSE;
                    }
                }
            } else {
                $first_id = FALSE;
            }
        }
        return $first_id;
    }

    //從cal.syoboi.jp取得某節目的維基百科連結
    public function get_title_wikipedia_link($src) {
        $result = array();
        $html = str_get_html($src);
        $link = $html->find('a[title^="Wikipedia:"]', 0)->href;
        return $link;
    }

    //從維基百科取得中文頁面
    public function get_zh_link_from_wikipedia($src) {
        $result = array();
        $html = str_get_html($src);
        $link = $html->find('li.interwiki-zh a', 0)->href;
        return $link;
    }

    //從維基百科取得中文標題
    public function get_zh_title_from_wikipedia($src) {
        $result = array();
        $html = str_get_html($src);
        $title = $html->find('h1#firstHeading', 0)->plaintext;
        return $title;
    }

    //取得快取內容
    public function get_url_cache($url) {
        $filename = $this->cache_dir . md5($url);
        //檢查檔案在不在
        if (file_exists($filename)) {
            //檢查快取過期了沒
            $file_t = filemtime($filename);
            if (!$file_t) {
                //快取無效
                $this->flush_url_cache($url);
                return false;
            } else {
                if ((time() - $file_t) > $this->cache_expire) {
                    //快取過期
                    $this->flush_url_cache($url);
                    return false;
                } else {
                    $data = file_get_contents($filename);
                    return $data;
                }
            }
        } else {
            return false;
        }
    }

    //清除快取內容
    public function flush_url_cache($url) {
        $filename = $this->cache_dir . md5($url);
        if (file_exists($filename)) {
            return unlink($filename);
        } else {
            return true;
        }
    }

    //設定快取內容
    public function set_url_cache($url, $data) {
        $filename = $this->cache_dir . md5($url);
        return file_put_contents($filename, $data);
    }

    //從網址取得資料
    public function get_page($url) {
        $this->msg(sprintf('正在從 %s 取得網頁內容....', urldecode($url)), FALSE);
        //嘗試從快取取得資料
        $data = $this->get_url_cache($url);
        if ($data) {
            $this->msg('成功');
            return $data;
        } else {
            //取得快取失敗, 直接打網址
            $data = file_get_contents($url);
            if ($data) {
                $this->msg('成功');
                $this->set_url_cache($url, $data);
            } else {
                $this->msg('失敗');
            }
        }
        return $data;
    }

    //輸出訊息
    public function msg($msg, $break = TRUE) {
        if ($break) {
            echo($msg . PHP_EOL);
        } else {
            echo($msg);
        }
        @ob_flush();
        flush();
    }
}
