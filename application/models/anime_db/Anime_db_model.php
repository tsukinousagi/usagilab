<?php

class Anime_db_model extends CI_Model {

    //快取設定
    public $cache_dir = 'cache/';
    public $cache_expire = 86400;


    function __construct() {
        parent::__construct();
        $this->load->library('simple_html_dom');
        $this->load->database();
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
        //記憶體
        ini_set('memory_limit', '256M');
        //延長執行時間
        set_time_limit(300);
        //限制取得筆數
        $limit = 1;
        //限制執行時間
        $bomb = 270;

        //計時開始
        $start = time();

        //cal.syoboi.jp節目清單分類參數
        $syoboi_jp_category = array(1,10,7,4,8);
        foreach ($syoboi_jp_category as $c) {
            //檢查計數
            if ($limit <= 0) {
                break;
            }
            //檢查執行時間
            if ((time() - $start) > $bomb) {
                break;
            }
            //取得所有動畫標題清單
            $src = $this->get_page(sprintf('http://cal.syoboi.jp/list?cat=%d', $c));
            $titles = $this->get_all_titles($src);
            foreach($titles as $t) {
                $this->msg(sprintf('處理項目: %s(%d)', $t['title'], $t['id']));
                //先查db看這作品是不是需要再處理
                $title_exists = $this->check_update_status($t['id']);
                if ($title_exists <> 'ok') {
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
                    $title_zh = '';
                    $wikipedia_link = $this->get_title_wikipedia_link($src);
                    if ($wikipedia_link <> '') {
                        //換成API的網址
                        $wikipedia_title_ja = str_replace('http://ja.wikipedia.org/wiki/', '', $wikipedia_link);
                        $this->msg(sprintf('日語維基條目名稱為: %s', $wikipedia_title_ja));
                        $wikipedia_link = sprintf('http://ja.wikipedia.org/w/api.php?action=query&prop=langlinks&format=xml&titles=%s&redirects=', $wikipedia_title_ja);
                        $src = $this->get_page($wikipedia_link);
                        $link_zh = $this->get_zh_link_from_wikipedia($src);
                        if ($link_zh <> '') {
                            $src = $this->get_page($link_zh);
                            $title_zh = $this->get_zh_title_from_wikipedia($src);
                            $this->msg(sprintf('作品中文標題為: %s', $title_zh));
                        } else {
                            $this->msg('找不到此作品的中文標題');
                        }
                    }
                    //寫入資料庫
                    $this->msg('寫入資料庫', 0);
                    $data = array(
                        'title_jp' => $t['title'],
                        'title_zh' => $title_zh,
                        'syoboi_jp_id' => $t['id'],
                        'parent_syoboi_jp_id' => $first,
                    );
                    $ret = FALSE;
                    if ($title_exists == 'update') {
                        $ret = $this->update_title($data);
                    } else if ($title_exists == 'create') {
                        $ret = $this->create_title($data);
                    }
                    if ($ret) {
                        $this->msg('成功');
                    } else {
                        $this->msg('失敗');
                    }
                    $this->msg(sprintf('目前記憶體用量: %d', memory_get_usage()));
                    //檢查計數
                    $limit--;
                    if ($limit <= 0) {
                        $this->msg('已達本次最大處理數目');
                        break;
                    }
                    //檢查執行時間
                    if ((time() - $start) > $bomb) {
                        $this->msg('已達執行時間限制');
                        break;
                    }
                } else {
                    $this->msg('略過');
                }
            }
        }

        $this->msg('已結束');
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
        $html->clear();
        $html = null;
        unset($html);
        return $result;
    }

    //從cal.syoboi.jp取得某節目的分類
    public function get_title_category($src) {
        $result = array();
        $html = str_get_html($src);
        $link = $html->find('a[rel=contents]', 0)->href;
        $html->clear();
        $html = null;
        unset($html);
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
                        $first_id = 0;
                    }
                }
            } else {
                $first_id = 0;
            }
        }
        $html->clear();
        $html = null;
        unset($html);
        return $first_id;
    }

    //從cal.syoboi.jp取得某節目的維基百科連結
    public function get_title_wikipedia_link($src) {
        $html = str_get_html($src);
        $link = $html->find('a[title^="Wikipedia:"]', 0);
        if (is_object($link)) {
            $href = $link->href;
        } else {
            $href = '';
        }
        $html->clear();
        $html = null;
        unset($html);
        return $href;
    }

    //從維基百科取得中文頁面
    public function get_zh_link_from_wikipedia($src) {
        $xml = simplexml_load_string($src);
        $zh_link_part = '';
        foreach ($xml->query->pages->page->langlinks->ll as $ll) {
            if ($ll['lang'] == 'zh') {
                $zh_link_part = $ll[0];
                break;
            }
        }
        if ($zh_link_part <> '') {
            $link = sprintf('http://zh.m.wikipedia.org/zh-tw/%s', $zh_link_part);
        }
        return $link;
    }

    //從維基百科取得中文標題
    public function get_zh_title_from_wikipedia($src) {
        $html = str_get_html($src);
        //桌面版網頁
//        $title = $html->find('h1#firstHeading', 0)->plaintext;
        //行動版網頁
        $title = $html->find('h1#section_0', 0)->plaintext;
        $html->clear();
        $html = null;
        unset($html);
        return $title;
    }

    //取得該作品更新狀態
    public function check_update_status($syoboi_jp_id) {
        $sql = "SELECT `update_flag` FROM `anime_title`
                WHERE `syoboi_jp_id` = %d";
        $sql = sprintf($sql, $syoboi_jp_id);
        $query = $this->db->query($sql);
        $ret = $query->result_array();
        if (sizeof($ret) > 0) {
            if ($ret[0]['update_flag'] == '1') {
                return 'update';
            } else {
                return 'ok';
            }
        } else {
            return 'create';
        }
    }

    //新增作品
    public function create_title($data) {
        $sql = sprintf("INSERT INTO `anime_title` (
                        `title_jp`, `title_zh`, `syoboi_jp_id`, `parent_syoboi_jp_id`, `update_flag`, `update_at`
                        ) VALUES (
                            '%s', '%s', %d, %d, %d, NOW())",
                        $data['title_jp'], $data['title_zh'], $data['syoboi_jp_id'],
                        $data['parent_syoboi_jp_id'], 0);
        $query = $this->db->query($sql);
        return $query;
    }

    //更新作品
    public function update_title($data) {
        $sql = sprintf("UPDATE `anime_title` SET
            `title_jp` = '%s',
            `title_zh` = '%s',
            `parent_syoboi_jp_id` = %d,
            `update_flag` = %d,
            `update_at` = NOW()
            WHERE
            `syoboi_jp_id` = %d
            ",
            $data['title_jp'], $data['title_zh'],
            $data['parent_syoboi_jp_id'], 0, $data['syoboi_jp_id']);
        $query = $this->db->query($sql);
        return $query;
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
            $data = file_get_contents($this->fix_url($url));
            if ($data) {
                $this->msg('成功');
                $this->set_url_cache($url, $data);
            } else {
                $this->msg('失敗');
            }
        }
        return $data;
    }

    //處理問題網址
    public function fix_url($url) {
        $url = str_replace(' ', '%20', $url);
        $url = str_replace('&amp;', '&', $url);
        return $url;
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
