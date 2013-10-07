<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Control\ExcelRead;

use phpManufaktur\Basic\Control\kitCommand\Basic;
use Silex\Application;

require_once MANUFAKTUR_PATH.'/CommandCollection/Control/ExcelRead/BiffWorkbook/CompoundDocument.inc.php';
require_once MANUFAKTUR_PATH.'/CommandCollection/Control/ExcelRead/BiffWorkbook/BiffWorkbook.inc.php';

class ExcelRead extends Basic
{
    /**
     * Initialize the iFrame for ExcelRead
     * Return the Welcome page if no file is specified. otherwise show the Excel file
     *
     * @param Application $app
     */
    public function initFrame(Application $app)
    {
        // initialize the Basic class
        $this->initParameters($app);
        // get the command parameters
        $parameter = $this->getCommandParameters();

        if (!isset($parameter['file'])) {
            // missing the 'file' parameter, so show the welcome file from Gist
            return $this->createIFrame('/basic/help/excelread/welcome');
        }
        else {
            // execute ExcelRead within the iFrame
            return $this->createIFrame('/collection/excelread/exec');
        }
    }

    /**
     * Show the specified Excel file
     *
     * @param Application $app
     * @throws \Exception
     * @return string rendered Excel file
     */
    public function exec(Application $app)
    {
        // initialize the Basic class
        $this->initParameters($app);
        // get the command parameters
        $parameter = $this->getCommandParameters();

        // check the parameter 'base'
        if (!isset($parameter['base'])) {
            $base = 'cms_media';
        }
        else {
            $base_array = array('cms_media', 'media', 'media_protected', 'path', 'url');
            $base = strtolower($parameter['base']);
            if (!in_array($base, $base_array)) {
                throw new \Exception("The value '$base' for the parameter 'base' is unknown!");
            }
        }

        // check the parameter 'file'
        if (!isset($parameter['file'])) {
            throw new \Exception("Missing the parameter 'file'!");
        }

        if ($base == 'url') {
            $file = trim($parameter['file']);
        }
        else {
            $file = $this->app['utils']->sanitizePath(trim($parameter['file']));
            if ($file[0] != '/') {
                $file = '/'.$file;
            }
        }

        // build the path
        switch ($base) {
            case 'cms_media':
                $path = CMS_MEDIA_PATH.$file; break;
            case 'media':
                $path = FRAMEWORK_MEDIA_PATH.$file; break;
            case 'media_protected':
                $path = FRAMEWORK_MEDIA_PROTECTED_PATH.$file; break;
            case 'url':
                $path = urlencode($file); break;
            case 'path':
                $path = $file; break;
            default:
                throw new \Exception("Missing the handling for the 'base' parameter with the value $base");
        }

        // check the path
        if (($base != 'url') && !file_exists($path)) {
            throw new \Exception("The file $file does not exists!");
        }

        $create_link = false;
        $create_mail = false;

        if (isset($parameter['create']) && !empty($parameter['create'])) {
            $create_array = explode(',', $parameter['create']);
            foreach ($create_array as $key => $value) {
                $value = trim(strtolower($value));
                switch ($value) {
                    case 'link':
                        $create_link = true; break;
                    case 'email':
                    case 'mail':
                        $create_mail = true; break;
                    default:
                        throw new \Exception("Unknown value '$value' for the parameter 'create'");
                }
            }
        }

        // set a target for the links
        $target = (isset($parameter['target'])) ? trim(strtolower($parameter['target'])) : '_top';
        if (empty($target)) {
            throw new \Exception("You must specify a valid value for the parameter 'target'");
        }
        if (!in_array($target, array('_blank', '_self', '_parent', '_top'))) {
            throw new \Exception("Unknown value '$target' for the parameter 'target'");
        }

        // include or exclude columns
        $xls_columns = (isset($parameter['column']) && !empty($parameter['column'])) ? explode(',', $parameter['column']) : array();
        $exclude_columns = false;
        $exclude_columns_array = array();
        $include_columns = false;
        $include_columns_array = array();
        foreach ($xls_columns as $col) {
            if (intval($col) < 0) {
                $exclude_columns = true;
                $exclude = ($col*-1)-1;
                if (!in_array($exclude, $exclude_columns_array))
                    $exclude_columns_array[] = $exclude;
            }
            else {
                $include_columns = true;
                $include = $col-1;
                if (!in_array($include, $include_columns_array))
                    $include_columns_array[] = $include;
            }
        }

        // include or exclude rows
        $xls_rows = (isset($parameter['row']) && !empty($parameter['row'])) ? explode(',', $parameter['row']) : array();
        $exclude_rows = false;
        $exclude_rows_array = array();
        $include_rows = false;
        $include_rows_array = array(0);
        foreach ($xls_rows as $row) {
            if (intval($row) < 0) {
                $exclude_rows = true;
                $exclude = ($row*-1);
                if (!in_array($exclude, $exclude_rows_array))
                    $exclude_rows_array[] = $exclude;
            }
            else {
                $include_rows = true;
                $include = $row;
                if (!in_array($include, $include_rows_array))
                    $include_rows_array[] = $include;
            }
        }

        // show only the specified sheets
        $xls_sheets = array(1);
        if (isset($parameter['sheet']) && !empty($parameter['sheet'])) {
            $sheets = explode(',', $parameter['sheet']);
            $xls_sheets = array();
            foreach ($sheets as $key => $value) {
                $xls_sheets[] = intval($value);
            }
        }

        $show_header = (!isset($parameter['header']) || (isset($parameter['header']) && (empty($parameter['header']) || (trim($parameter['header'] == 1) || (strtolower(trim($parameter['header'])) == 'true'))))) ? true : false;

        // style of the columns
        $columns_style = array();
        if (isset($parameter['style']) && !empty($parameter['style'])) {
            $style_array = explode(',', $parameter['style']);
            foreach ($style_array as $item) {
                if (strpos($item, '|')) {
                    list($column, $style) = explode('|', strtolower(trim($item)));
                    $columns_style[$column-1] = $style;
                }
            }
        }

        // format of the columns
        $columns_format = array();
        if (isset($parameter['format']) && !empty($parameter['format'])) {
            $format_array = explode(',', $parameter['format']);
            foreach ($format_array as $item) {
                if (strpos($item, '|')) {
                    list($column, $format) = explode('|', trim($item));
                    if ((false !== ($pos = stripos(trim($format), 'date'))) && ($pos == 0)) {
                        if ((false !== ($start = stripos($format, '('))) && (false !== ($end = stripos($format, ')')))) {
                            $date_format = trim(substr($format, $start+1, $end-($start+1)));
                            $date_format = str_ireplace('comma', ',', $date_format);
                        }
                        else {
                            $date_format = $app['translator']->trans('DATE_FORMAT');
                        }
                        $columns_format[trim($column-1)]['date']['format'] = $date_format;
                    }
                    else {
                        $columns_format[trim($column-1)] = strtolower(trim($format));
                    }
                }
            }
        }

        // create new Excel Document from the given file
        $doc = new \CompoundDocument('utf-8');
        $doc->parse(file_get_contents($path));
        $xls = new \BiffWorkbook($doc);
        $xls->parse();

        $excel = array();


        $i=0;
        foreach ($xls->sheets as $sheetName => $sheet) {
            $i++;
            if (is_array($xls_sheets) && !in_array($i, $xls_sheets)) {
                // show only the specified Excel sheets
                continue;
            }
            $add_sheet = array();
            $add_sheet['name'] = $sheetName;
            for ($row = 0; $row < $sheet->rows(); $row++) {
                if (($row == 0) && !$show_header) {
                    // skip header line
                    continue;
                }
                if ($include_rows && !in_array($row, $include_rows_array)) {
                    // use only the specified rows
                    continue;
                }
                if ($exclude_rows && in_array($row, $exclude_rows_array)) {
                    // exclude the specified rows
                    continue;
                }
                // new row
                $add_row = array();
                $column_count = 0;
                for ($col = 0; $col < $sheet->cols(); $col++) {
                    if ($include_columns && !in_array($col, $include_columns_array)) {
                        // use only the specified columns
                        continue;
                    }
                    if ($exclude_columns && in_array($col, $exclude_columns_array)) {
                        // exclude the specified columns
                        continue;
                    }
                    if (!isset($sheet->cells[$row][$col])) {
                        $add_row[] = array(
                            'content' => null,
                            'style' => isset($columns_style[$col]) ? $columns_style[$col] : '',
                            'format' => isset($columns_format[$column_count]) ? $columns_format[$column_count] : ''
                        );
                    }
                    else {
                        $row_content = (is_null($sheet->cells[$row][$col]->value)) ? null : utf8_encode($sheet->cells[$row][$col]->value);
                        if ($create_link) {
                            // check for URL's and create a HTML Link
                            preg_match_all('/\b(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)[-A-Z0-9+&@#\/%=~_|$?!:,.]*[A-Z0-9+&@#\/%=~_|$]/i',
                                $row_content, $matches, PREG_SET_ORDER);
                            foreach ($matches as $match) {
                                $url = $match[0];
                                if ((false === (stripos($match[0], 'http://'))) && (false === (stripos($match[0], 'https://')))) {
                                    // add the protocol if missing
                                    $url = sprintf('http://%s', $match[0]);
                                }
                                $add_target = (!empty($target)) ? sprintf(' target="%s"', $target) : '';
                                $row_content = str_replace($match[0], sprintf('<a href="%s"%s>%s</a>', $url, $add_target, $match[0]), $row_content);
                            }
                        }
                        if ($create_mail) {
                            // check for emails and create HTML links
                            preg_match_all('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}\b/i', $row_content, $matches, PREG_SET_ORDER);
                            foreach ($matches as $match) {
                                $row_content = str_replace($match[0], sprintf('<a href="mailto:%1$s">%1$s</a>', $match[0]), $row_content);
                            }
                        }
                        if (isset($columns_format[$column_count]['date']['format'])) {
                            if (is_int($sheet->cells[$row][$col]->value) && ($sheet->cells[$row][$col]->value < 100000)) {
                                // date is a Excel serial number date, convert it to a unix timestamp
                                $row_content = mktime(0, 0, 0, 1, $sheet->cells[$row][$col]->value-1, 1900);
                            }
                        }
                        // add the row content
                        $add_row[] = array(
                            'content' => $row_content,
                            'style' => isset($columns_style[$column_count]) ? $columns_style[$column_count] : '',
                            'format' => isset($columns_format[$column_count]) ? $columns_format[$column_count] : ''
                        );
                        $column_count++;
                    }
                }
                // add the row to the sheet
                $add_sheet['row'][] = $add_row;
            }
            // add the sheet
            $excel[] = $add_sheet;
        }

        // return the excel file
        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/CommandCollection/Template/ExcelRead',
            "table.twig",
            $this->getPreferredTemplateStyle()),
            array(
                'basic' => $this->getBasicSettings(),
                'excel' => $excel,
                'count' => array(
                    'columns' => $column_count,
                    'rows' => count($excel)
                ),
                'option' => array(
                    'title' => (isset($parameter['title']) && (empty($parameter['title']) || (trim($parameter['title']) == 1) || (strtolower(trim($parameter['title'])) == 'true'))) ? true : false,
                    'header' => $show_header,
                    'tablesorter' => (isset($parameter['tablesorter']) && (empty($parameter['tablesorter']) || (trim($parameter['tablesorter']) == 1) || (strtolower(trim($parameter['tablesorter'])) == 'true'))) ? true : false,
                    ),
                'format' => $columns_format
            ));
    }
}
