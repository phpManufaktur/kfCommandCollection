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

use Silex\Application;

require_once MANUFAKTUR_PATH.'/CommandCollection/Control/ExcelRead/BiffWorkbook/CompoundDocument.inc.php';
require_once MANUFAKTUR_PATH.'/CommandCollection/Control/ExcelRead/BiffWorkbook/BiffWorkbook.inc.php';

class Search
{
    /**
     * Search function for the kitCommand ExcelRead
     *
     * @param Application $app
     * @return string
     */
    public function controllerSearch(Application $app)
    {
        $app['monolog']->addInfo('Enter search function for kitCommand ExcelRead', array(__METHOD__, __LINE__));

        $search = $app['request']->get('search');
        $search['success'] = false;
        $parameter = $app['request']->get('parameter');

        // check the parameter 'base'
        if (!isset($parameter['base'])) {
            $base = 'cms_media';
        }
        else {
            $base_array = array('cms_media', 'media', 'media_protected', 'path', 'url');
            $base = strtolower($parameter['base']);
            if (!in_array($base, $base_array)) {
                $app['monolog']->addError("The value '$base' for the parameter 'base' is unknown!", array(__METHOD__, __LINE__));
                return base64_encode(json_encode(array('search' => $search)));
            }
        }
        // check the parameter 'file'
        if (!isset($parameter['file'])) {
            $app['monolog']->addError("Missing the parameter 'file'!", array(__METHOD__, __LINE__));
            return base64_encode(json_encode(array('search' => $search)));
        }

        if ($base == 'url') {
            $file = trim($parameter['file']);
        }
        else {
            $file = $app['utils']->sanitizePath(trim($parameter['file']));
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
                $app['monolog']->addError("Missing the handling for the 'base' parameter with the value $base", array(__METHOD__, __LINE__));
                return base64_encode(json_encode(array('search' => $search)));
        }

        // check the path
        if (($base != 'url') && !file_exists($path)) {
            $app['monolog']->addError("The file $file does not exists!", array(__METHOD__, __LINE__));
            return base64_encode(json_encode(array('search' => $search)));
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

        // create new Excel Document from the given file
        $doc = new \CompoundDocument('utf-8');
        $doc->parse(file_get_contents($path));
        $xls = new \BiffWorkbook($doc);
        $xls->parse();

        $i=0;
        $search_result = '';
        foreach ($xls->sheets as $sheetName => $sheet) {
            $i++;
            if (is_array($xls_sheets) && !in_array($i, $xls_sheets)) {
                // show only the specified Excel sheets
                continue;
            }
            $add_sheet = array();
            $add_sheet['name'] = $sheetName;
            for ($row = 0; $row < $sheet->rows(); $row++) {
                if ($row == 0)  {
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
                for ($col = 0; $col < $sheet->cols(); $col++) {
                    if ($include_columns && !in_array($col, $include_columns_array)) {
                        // use only the specified columns
                        continue;
                    }
                    if ($exclude_columns && in_array($col, $exclude_columns_array)) {
                        // exclude the specified columns
                        continue;
                    }
                    if (!is_null($sheet->cells[$row][$col]->value)) {
                        $search_result .= ' '.utf8_encode($sheet->cells[$row][$col]->value);
                    }
                }
            }
        }

        if (!empty($search_result)) {
            $search['text'] = $search_result;
            $search['success'] = true;
        }
        return base64_encode(json_encode(array('search' => $search)));
    }
}
