<?php
/**
 * 簡単な記述で表を組むための mediawiki 拡張
 *
 * @author Endo Hiroaki <do.hiroaki@gmail.com>
 * @version 0.1
 * @history 2010/06/11 最初のリリース
 *
 *
 * 使用例
 *  <tbl>
 *  |th:w100px: 1|ac: 2|al: 3|
 *  |ac:th: A|rs2: B|
 *  </tbl>
 * 
 * 説明
 *  カラムの区切りは '|' (DELIM_COLUMN で変更可能)
 *  カラムの中で、 ': ' がオプションとカラムの区切りとなる
 *  オプションは ':' 区切り
 *  オプションは以下の通り
 *
 *  th       : td の代わりに th を利用
 *  al ar ac : 順に、右寄せ、左寄せ、中央揃え
 *  w[size]  : widthを指定
 *  rs       : rowspan を指定
 *  cs       : colspan を指定
 */
$wgExtensionFunctions[] = array('SimpleTable', 'setup');

class SimpleTable {
    
    // カラムを区切るデリミタ
    const DELIM_COLUMN = '|';
    // オプションを区切るデリミタ
    const DELIM_OPT = ':';
    // オプションとカラムを区切るデリミタ
    const DELIM_OPT_COLUM = ' ';

    // 初期化
    public static function setup() {
        global $wgParser;
        $wgParser->setHook('TBL', array(__CLASS__, 'hookTblTag'));
    }

    /**
     * フックメソッド (ページ保存/プレビュー時に呼ばれるっぽ)
     *
     * @param string $body  中身の文字列
     * @param string $attrs タグの属性
     * @param array  $parser よーしらん
     * @return string HTML
     */
    public static function hookTblTag($body, $attrs, $parser) {
        
        if (!isset($attrs['border'])) {
            $attrs['border'] = '1';
        }
        if (!isset($attrs['cellspacing'])) {
            $attrs['cellspacing'] = '0';
        }
        
        $ret = '';
        $ret .= '<table';
        foreach($attrs as $k => $v) {
            $ret .= ' ' . $k . '="' . $v .'"';
        }
        $ret .= '><tbody>';

        foreach(explode("\n", $body) as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            $ret .= "\n" . '<tr>';
            foreach (explode(self::DELIM_COLUMN, trim($line, self::DELIM_COLUMN)) as $column) {
                $opts = array(
                    'tag'  => 'td',
                );
                if ($pos = strpos($column, self::DELIM_OPT . self::DELIM_OPT_COLUM)) { // 先頭に存在する場合は、内容と見なすので、 false  Strict check はしない
                    $opts = array_merge($opts, self::_parseOptions(explode(self::DELIM_OPT, substr($column, 0, $pos))));
                    $column = substr($column, strlen(self::DELIM_OPT . self::DELIM_OPT_COLUM)+$pos);
                }

                $ret .= self::_buildColumn($column, $opts);
            }
            $ret .= '</tr>';
        }
        $ret .= '</tbody></table>';
        
        return $ret;
    }
    
    /**
     * 1カラム構築
     *
     * @param string $column カラム内容
     * @param array  $opts   正規化されたオプション (ref:_parseOptions)
     * @return string 構築されたカラム
     */
    private static function _buildColumn($column, $opts) {
        $style = '';
        if (isset($opts['align'])) {
            $style .= 'text-align: '. $opts['align'] . ';';
        }
        if (isset($opts['width'])) {
            $style .= 'width: '. $opts['width'] . ';';
        }
        
        $attr = '';
        if (isset($opts['rowspan'])) {
            $attr .= ' rowspan="'.$opts['rowspan'].'"';
        }
        if (isset($opts['colspan'])) {
            $attr .= ' colspan="'.$opts['colspan'].'"';
        }
        if ($style) {
            $attr .= ' style="' . $style . '"';
        }

        if (' ' === $column[0]) { // 先頭のスペースは１つだけ取り除く(体裁のため)
            $column = substr($column, 1);
        }

        return '<' . $opts['tag'] . $attr . '>'
            . str_replace(' ', '&nbsp;', rtrim($column))
            . '</' . $opts['tag'] . '>';
    }

    /**
     * オプションを解析
     *
     * @param array  $opts   オプションの配列
     * @return array 正規化されたオプション：詳細は以下の通り
     *  
     *  'tag'     カラムを構成するタグ名(必須)
     *  'align'   幅寄せ指定
     *  'width'   幅指定
     *  'rowspan' rowspan 要素
     *  'colspan' colspan 要素
     */
    private static function _parseOptions($opts) {
        $ret = array();
        foreach($opts as $opt) {
            $opt = strtolower($opt);
            if (empty($opt)) {
                continue;
            }

            switch($opt[0]) {
                case 'a':
                    $tbl = array(
                        'c' => 'center',
                        'r' => 'right',
                        'l' => 'left',
                    );

                    if (isset($opt[1]) && isset($tbl[$opt[1]])) {
                        $ret['align'] = $tbl[$opt[1]];
                    }
                    break;
                case 'w':
                    $ret['width'] = substr($opt, 1);
                    break;
                case 'r':
                    if (isset($opt[1]) && 's' === $opt[1]) {
                        $ret['rowspan'] = intval(substr($opt, 2));
                    }
                    break;

                case 'c':
                    if (isset($opt[1]) && 's' === $opt[1]) {
                        $ret['colspan'] = intval(substr($opt, 2));
                    }
                    break;
                case 't':
                    if (isset($opt[1]) && 'h' === $opt[1]) {
                        $ret['tag'] = 'th';
                    }
                    break;
            }
        }
        return $ret;
    }

}
