<?php

class Anime_db_model extends CI_Model {

    //快取設定
    public $cache_dir = 'cache/';
    public $cache_expire = 86400;

    //暫存db的title狀態
    public $valid_syoboi_jp_id;
    public $update_syoboi_jp_id;


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
        }
        echo('Done!');
    }

    public function fetch_title() {
        //記憶體
        ini_set('memory_limit', '256M');
        //延長執行時間
        set_time_limit(300);
        //限制取得筆數
        $limit = 999;
        //限制執行時間
        $bomb = 270;

        //計時開始
        $start = time();

        //取得syoboi.jp所有節目清單
        $src = $this->get_page('http://cal.syoboi.jp/db.php?Command=TitleLookup&TID=*&Fields=TID,Title,Cat');
//        $src = $this->get_page('http://cal.syoboi.jp/db.php?Command=TitleLookup&TID=3595,2937,1487,2691&Fields=TID,Title,Cat');
        $titles = $this->get_all_titles($src);
        //取得目前資料庫資料正確的tid
        $this->valid_syoboi_jp_id = $this->get_current_titles(0);
        $this->update_syoboi_jp_id = $this->get_current_titles(1);
        //丟進迴圈跑, 略過不需要處理的項目

        foreach($titles as $t) {
            //$this->msg(sprintf('處理項目: %s(%d)', $t['title'], $t['id']));
            //$this->msg('.', 0);
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
                $title_en = '';
                $wikipedia_link = $this->get_title_wikipedia_link($src);
                if ($wikipedia_link <> '') {
                    //換成API的網址
                    $wikipedia_title_ja = str_replace('http://ja.wikipedia.org/wiki/', '', $wikipedia_link);
                    $this->msg(sprintf('日語維基條目名稱為: %s', $wikipedia_title_ja));
                    $wikipedia_link_jp = sprintf('http://ja.wikipedia.org/w/api.php?action=query&prop=langlinks&format=xml&titles=%s&redirects=&continue=', $wikipedia_title_ja);
                    $src_jp = $this->get_page($wikipedia_link_jp);
                    //中文連結
                    $link_zh = $this->get_zh_link_from_wikipedia_api($src_jp);
                    if ($link_zh <> '') {
                        $src = $this->get_page($link_zh);
                        $title_zh = $this->get_zh_title_from_wikipedia($src);
                        $this->msg(sprintf('作品中文標題為: %s', $title_zh));
                    } else {
                        //如果api找不到, 再次嘗試從頁面找中文標題
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
                    //英文連結
                    $link_en = $this->get_en_link_from_wikipedia_api($src_jp);
                    if ($link_en <> '') {
                        $src = $this->get_page($link_en);
                        $title_en = $this->get_zh_title_from_wikipedia($src);
                        $this->msg(sprintf('作品英文標題為: %s', $title_en));
                    } else {
                        //如果api找不到, 再次嘗試從頁面找英文標題
                        $src = $this->get_page($wikipedia_link);
                        $link_en = $this->get_en_link_from_wikipedia($src);
                        if ($link_en <> '') {
                            $src = $this->get_page($link_en);
                            $title_en = $this->get_zh_title_from_wikipedia($src);
                            $this->msg(sprintf('作品英文標題為: %s', $title_en));
                        } else {
                            $this->msg('找不到此作品的英文標題');
                        }
                    }
                } else {
                    $this->msg('cal.syoboi.jp沒有維基百科連結');
                }
                //寫入資料庫
                $this->msg('寫入資料庫', 0);
                $data = array(
                    'title_jp' => $t['title'],
                    'title_zh' => $title_zh,
                    'title_en' => $title_en,
                    'syoboi_jp_id' => intval($t['id']),
                    'parent_syoboi_jp_id' => intval($first),
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
                //$this->msg('略過');
                $this->msg('.', 0);
            }
        }

        $this->msg('已結束');
    }

    //從cal.syoboi.jp取得所有動畫節目標題
    public function get_all_titles($src) {
        $result = array();
        //old
        /*
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
         */
        //new
        $xml = simplexml_load_string($src);
        if ($xml->Result->Code <> '200') {
            return FALSE;
        } else {
            //有效的節目分類
            $valid_cats = array(1,10,7,4,8);
            foreach($xml->TitleItems->TitleItem as $t) {
                $t2 = (array)$t;
                if (in_array($t->Cat, $valid_cats)) {
                    $title = array('id' => $t2['TID'], 'title' => $t2['Title']);
                    $result[] = $title;
                }
            }
        }
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
    public function get_zh_link_from_wikipedia_api($src) {
        $xml = simplexml_load_string($src);
        $zh_link_part = '';
        if (isset($xml->query->pages->page->langlinks->ll)) {
            foreach ($xml->query->pages->page->langlinks->ll as $ll) {
                if ($ll['lang'] == 'zh') {
                    $zh_link_part = $ll[0];
                    break;
                }
            }
            if ($zh_link_part <> '') {
                $link = sprintf('http://zh.m.wikipedia.org/zh-tw/%s', $zh_link_part);
            } else {
                $link = '';
            }
            return $link;
        } else {
            return FALSE;
        }
    }

    //從維基百科取得英文頁面
    public function get_en_link_from_wikipedia_api($src) {
        $xml = simplexml_load_string($src);
        $en_link_part = '';
        if (isset($xml->query->pages->page->langlinks->ll)) {
            foreach ($xml->query->pages->page->langlinks->ll as $ll) {
                if ($ll['lang'] == 'en') {
                    $en_link_part = $ll[0];
                    break;
                }
            }
            if ($en_link_part <> '') {
                $link = sprintf('http://en.m.wikipedia.org/wiki/%s', $en_link_part);
            } else {
                $link = '';
            }
            return $link;
        } else {
            return FALSE;
        }
    }

    //從維基百科取得中文頁面
    public function get_zh_link_from_wikipedia($src) {
        $html = str_get_html($src);
        if (is_object($html)) {
            $link = $html->find('li.interwiki-zh a', 0);
            if (is_object($link)) {
                $href = $link->href;
                //給行動版網址
                $href = str_replace('zh.wikipedia.org/wiki/', 'zh.m.wikipedia.org/zh-tw/', $href);
                $href = 'http:' . $href;
            } else {
                $href = '';
            }
            $html->clear();
        } else {
            $href = '';
        }
        $html = null;
        unset($html);
        return $href;
    }

    //從維基百科取得英文頁面
    public function get_en_link_from_wikipedia($src) {
        $html = str_get_html($src);
        if (is_object($html)) {
            $link = $html->find('li.interwiki-en a', 0);
            if (is_object($link)) {
                $href = $link->href;
                //給行動版網址
                $href = str_replace('en.wikipedia.org/wiki/', 'en.m.wikipedia.org/wiki/', $href);
                $href = 'http:' . $href;
            } else {
                $href = '';
            }
            $html->clear();
        } else {
            $href = '';
        }
        $html = null;
        unset($html);
        return $href;
    }

    //從維基百科取得中文標題
    public function get_zh_title_from_wikipedia($src) {
        $html = str_get_html($src);
        if (is_object($html)) {
            //桌面版網頁
    //        $title = $html->find('h1#firstHeading', 0)->plaintext;
            //行動版網頁
            $title = $html->find('h1#section_0', 0)->plaintext;
            $html->clear();
            $html = null;
            unset($html);
            return $title;
        } else {
            return '';
        }
    }

    //取得該作品更新狀態
    public function check_update_status($syoboi_jp_id) {
        if ($syoboi_jp_id <= 0) {
            $this->msg('為什麼id會有0, 這絕對很奇怪啊.');
            die();
        } else {
            if (in_array($syoboi_jp_id, $this->update_syoboi_jp_id)) {
                return 'update';
            } else if (in_array($syoboi_jp_id, $this->valid_syoboi_jp_id)) {
                return 'ok';
            } else {
                return 'create';
            }
        }
    }

    //新增作品
    public function create_title($data) {
        $sql = sprintf("INSERT INTO `anime_title` (
                        `title_jp`, `title_zh`, `title_en`, `syoboi_jp_id`, `parent_syoboi_jp_id`, `update_flag`, `update_at`
                        ) VALUES (
                            %s, %s, %s, %d, %d, %d, NOW())",
                            $this->db->escape($data['title_jp']),
                            $this->db->escape($data['title_zh']),
                            $this->db->escape($data['title_en']),
                            $this->db->escape($data['syoboi_jp_id']),
                            $this->db->escape($data['parent_syoboi_jp_id']), 0);
        $query = $this->db->query($sql);
        return $query;
    }

    //更新作品
    public function update_title($data) {
        $sql = sprintf("UPDATE `anime_title` SET
            `title_jp` = %s,
            `title_zh` = %s,
            `title_en` = %s,
            `parent_syoboi_jp_id` = %d,
            `update_flag` = %d,
            `update_at` = NOW()
            WHERE
            `syoboi_jp_id` = %d
            ",
            $this->db->escape($data['title_jp']), $this->db->escape($data['title_zh']), $this->db->escape($data['title_en']),
            $this->db->escape($data['parent_syoboi_jp_id']), 0, $this->db->escape($data['syoboi_jp_id']));
        $query = $this->db->query($sql);
        return $query;
    }

    //取得目前資料庫資料正確的TID
    public function get_current_titles($flag) {
        $sql = "SELECT `syoboi_jp_id` FROM `anime_title` WHERE
                `update_flag` = %d";
        $sql = sprintf($sql, $this->db->escape($flag));
        $query = $this->db->query($sql);
        if ($query) {
            $ret = $query->result_array();
            $valid_ids = array();
            foreach($ret as $v) {
                $valid_ids[] = $v['syoboi_jp_id'];
            }
            return $valid_ids;
        } else {
            return FALSE;
        }
    }

    //取得資料庫內容用JSON輸出
    public function api_get_titles($begin, $count) {
        $sql = "SELECT `syoboi_jp_id`, `title_jp`, `title_zh`, `title_en`, `title_en`
            FROM `anime_title`
            ORDER BY `syoboi_jp_id` ASC
            LIMIT %d, %d";
        $sql = sprintf($sql, $begin, $count);
        $query = $this->db->query($sql);
        if ($query) {
            $ret = $query->result_array();
        } else {
            $ret = array();
        }
        return $ret;
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
        $url = str_replace("&#039;", "%27", $url);
        $url = str_replace("&amp;", "%26", $url);
//        $url = str_replace('&amp;', '&', $url);
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
