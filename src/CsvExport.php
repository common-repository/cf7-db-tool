<?php
namespace CF7DBTOOL;
class CsvExport
{
	/**
	 * hold config values
	 * @var object
	 */
	private $config;
	/**
	 * id of the form
	 * @var int
	 */
	private $formId;
	/**
	 * entry ids to export
	 * @var int | string
	 */
	private $entryIds;
	/**
	 * method __construct()
	 * @param array
	 */
	public function __construct(array $args)
	{
		$this->config = $args['config'];
		$this->formId = $args['formId'];
		$this->entryIds = $args['entryIds'];
		$this->performCsvDownload();
	}
	/**
	 * perform csv download
	 * @return void;
	 */
	public function performCsvDownload()
	{
		$this->_setHeader('CF7DBT-entries-'.date("Y_m_d\-H_i:s").'.csv');
		echo $this->_generateCsvFile($this->_prepareData());
		die;
	}
	/**
	 * prepare associative array for csv file
	 * handle empty values
	 * @return array;
	 */
	private function _prepareData()
	{
		if(is_array($this->entryIds)){
			$entryIds = '';
			foreach ($this->entryIds as $entry) {
				$entryIds .= $entry . ',';
			}
			$entryIds = rtrim($entryIds, ',');
			$entries = $this->config->wpdb->get_results(
				"SELECT * FROM ".$this->config->entriesTable." WHERE id IN (" . $entryIds . ")"
			);
		}else{
			$entries = $this->config->wpdb->get_results(
				"SELECT * FROM ".$this->config->entriesTable." WHERE form_id =" . $this->formId
			);
		}

		foreach ($entries as $entry){
			$values = unserialize($entry->fields);
			$values = array_merge($values,[
					'status'=>$entry->status == 'failed'?__('Mail sent failed','cf7-db-tool'): __('Mail send success', 'cf7-db-tool'),
					'submit-time'=>date('F j, Y, g:i a',strtotime($entry->time))
			]);
			$updatedValues = array();
			foreach ($values as $key=>$value){
				if(empty($value)){
					$updatedValues[$key] = 'No data';
				}else{
					$updatedValues[$key] = $value;
				}
			}
			$entryValues[] = $updatedValues;
		}
		return $entryValues;

	}
	/**
	 * set headers
	 * @param string
	 * @return void;
	 */
	private function _setHeader( $filename )
	{
		$now = gmdate("D, d M Y H:i:s");
		header("Expires: Sun, 08 Feb 2019 06:00:00 GMT+6");
		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
		header("Last-Modified: {$now} GMT");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment;filename={$filename}");
		header("Content-Transfer-Encoding: binary");

	}
	/**
	 * set headers
	 * @param string
	 * @return mixed
	 */
	private function _generateCsvFile($data)
	{
		ob_start();
		if(isset($data['0'])){
			$fp = fopen('php://output', 'w');
			$headerArray = array();
			foreach (array_keys($data['0']) as $headerText){
				$headerArray[] = strtoupper($headerText);
			}
			fputcsv($fp, $headerArray);
			foreach($data as $values){
				fputcsv($fp, $values);
			}
			fclose($fp);
		}
		return ob_get_clean();
	}
}