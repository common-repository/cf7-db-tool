<?php
namespace CF7DBTOOL;
class Report extends  Plugin
{
    /**
     * hold config values
     * @var object
     */
    private $config;
    /**
     * method construct
     */
    public function __construct($config)
    {
        $this->config = $config;
        add_action('wpcf7_report', [$this, 'renderReport']);
        add_action('plugins_loaded', [$this, 'cf7AllEntry']);
    }


    /**
     * Render report html
     */
    public function renderReport(){

        ob_start();

        ?>
        <div class="wrap">
            <div class="cf7-dbt-container report">

                <div class="cf7-dbt-content">
                    <div class="report-header" style="display: flex;justify-content: space-between;align-items: center;padding: 0 1.3% 0 2%;">
                        <h2 id="report_label">Last 7 Days Report</h2>
                        <select name="" id="filter_by">
                            <option value="0" selected>Last 7 days</option>
                            <option value="1"  >By Last Year</option>
                        </select>
                    </div>
                    <canvas id="myChart" style="width:100%; height:500px;"> </canvas>
                </div>
	            <?php
                // Inlcue rating sidebar
	            $sidebar_template = plugin_dir_path(__FILE__) . 'sidebar.php';

	            if(file_exists($sidebar_template)){
		            include_once $sidebar_template;
	            }
	            ?>
            </div>
        </div>

        <?php
        return ob_get_flush();

    }

    /**
     * Dynamic report generate
     */
    public function cf7AllEntry()
    {

        $days = $this->getLastNDays();
        $last_seven_days_success=$this->reportSuccess();
        $last_seven_days_fail=$this->reportFailed();
        $monthly_success =  $this->reportSuccessMonthly();
        $monthly_Failed =  $this->reportFailedMonthly();


        ?>

        <script>
            document.addEventListener("DOMContentLoaded", function(event) {
                var days=[];
                <?php
                    foreach ($days as $day){ ?>
                days.push(<?php echo $day ?>)
                   <?php }
                ?>

                var months =[];

               <?php foreach ($monthly_success as $month=>$val){ ?>
                    months.push('<?php echo $month ?>');
              <?php  } ?>

                var lebelsData =[
                    days,
                    months
                ];


                var receiveDataDays = [];

                <?php foreach ($last_seven_days_success as $days){ ?>
                receiveDataDays.push('<?php echo $days ?>');
                <?php }
                ?>

                var receiveDataMonths = [];

                <?php foreach ($monthly_success as $month){ ?>
                receiveDataMonths.push('<?php echo $month ?>');
                <?php }
                ?>

                var receiveDatas = [
                    receiveDataDays,
                    receiveDataMonths
                ]



                var failedDataDays = [];

                <?php foreach ($last_seven_days_fail as $days){ ?>
                failedDataDays.push('<?php echo $days ?>');
                <?php }
                ?>

                var failedDataMonths = [];

                <?php foreach ($monthly_Failed as $month){ ?>
                failedDataMonths.push('<?php echo $month ?>');
                <?php }
                ?>

                var failedDatas =[
                    failedDataDays,
                    failedDataMonths
                ]

                // Chart filter
                function chartLoad(filterBy=0){


                    var Headingcontent =document.querySelector('#report_label');

                    if(filterBy==1){
                        Headingcontent.textContent ="Last 12 Month Report";
                    }else{

                        Headingcontent.textContent ="Last 7 Days Report";
                    }



                    var ctx = document.getElementById('myChart').getContext('2d');
                    var chart = new Chart(ctx, {
                        // The type of chart we want to create
                        type: 'line',

                        // The data for our dataset
                        data: {
                            labels: lebelsData[filterBy],
                            datasets: [
                                {
                                    label: 'Mail Sent',
                                    backgroundColor: 'rgb(122,208,58)',
                                    borderColor: 'rgb(122,208,58)',
                                    fill: false,
                                    data: receiveDatas[filterBy],
                                },
                                {
                                    label: 'Mail Failed',
                                    backgroundColor: 'red',
                                    borderColor: 'red',
                                    fill: false,
                                    data: failedDatas[filterBy],
                                }

                            ]
                        },


                        // Configuration options go here
                        options: {}
                    });

                }

                chartLoad();


                // Change filter option
                document.querySelector('#filter_by').addEventListener('change', function(e){
                    chartLoad(e.target.value)
                })

               // console.log(filterBy);



            });
        </script>

        <?php

    }

    /**
     * Get last 7 days
     * @return void
     */
    public function getLastNDays($days = 7, $format = 'M/d'){
        $m = date("m"); $de= date("d"); $y= date("Y");
        $dateArray = array();
        for($i=0; $i<=$days-1; $i++){
            $dateArray[] = '"' . date($format, mktime(0,0,0,$m,($de-$i),$y)) . '"';
        }

        $last7Days = [];
        foreach($dateArray as $day){
            $last7Days[] = str_replace('/',' ', $day);
        }
       return array_reverse($last7Days);
    }



    /**
     * get success row
     * @return void
     */
    public function reportSuccess(){

        $entryQueriesSuccess = $this->_getRowFromDb($this->config->entriesTable, 'sent');
        $last_seven_days_success=[];

        for($i=0;$i<7;$i++){
            $last_seven_days_success[date('Y-m-d',strtotime('-'.$i.'Days'))]=0;
        }

        foreach ($entryQueriesSuccess as $query ){
            if(isset($last_seven_days_success[$query->time])){
                $last_seven_days_success[$query->time]=$query->count;
            }
        }

       return  $last_seven_days_success= array_reverse($last_seven_days_success);
    }

    /**
     * get failed row
     * @return void
     */
    public function reportFailed(){
        $entryQueriesFailed = $this->_getRowFromDb($this->config->entriesTable, 'failed');
        $last_seven_days_fail=[];

        for($i=0;$i<7;$i++){
            $last_seven_days_fail[date('Y-m-d',strtotime('-'.$i.'Days'))]=0;
        }

        foreach ($entryQueriesFailed as $query ){
            if(isset($last_seven_days_fail[$query->time])){
                $last_seven_days_fail[$query->time]=$query->count;
            }
        }

        return $last_seven_days_fail= array_reverse($last_seven_days_fail);
    }

    /**
     * get success row
     * @return void
     */
    public function reportSuccessMonthly(){

        $entryQueriesSuccess = $this->_getMonthCountRowFromDb($this->config->entriesTable, 'sent');
        $monthly_report=[];


        for($i=0;$i<12;$i++){
            $monthly_report[date('F', mktime(0, 0, 0, $i, 1, date("Y")))]=0;
        }

        foreach ($entryQueriesSuccess as $query ){
            $month_name = date("F", mktime(0, 0, 0, $query->time));
                if(isset($monthly_report[$month_name])){
                $monthly_report[$month_name]=$query->count;
            }
        }

     return  $monthly_report;
    }

    /**
     * get failed row
     * @return void
     */
    public function reportFailedMonthly(){
        $entryQueriesFailed = $this->_getMonthCountRowFromDb($this->config->entriesTable, 'failed');

        $monthly_report=[];


        for($i=0;$i<12;$i++){
            $monthly_report[date('F', mktime(0, 0, 0, $i, 1, date("Y")))]=0;
        }

        foreach ($entryQueriesFailed as $query ){

            $month_name = date("F", mktime(0, 0, 0, $query->time));
            if(isset($monthly_report[$month_name])){
                $monthly_report[$month_name]=$query->count;
            }
        }

        return  $monthly_report;
    }


    /**
     * get query result
     *
     */
    private function _getRowFromDb($table, $status)

    {
        return $this->config->wpdb->get_results("SELECT count(id) as count,  DATE(`time`) as time, status FROM $table  WHERE DATE(`time`) > DATE(NOW() - INTERVAL 7 DAY) AND status='$status'  group by DATE(`time`)");
    }

    /**
     * get query result by month
     *
     */
    private function _getMonthCountRowFromDb($table, $status)

    {
        return $this->config->wpdb->get_results("SELECT count(id) as count,  MONTH(`time`) as time, status FROM $table  WHERE YEAR(`time`) = YEAR(NOW()) AND status='$status'  group by MONTH(`time`)");
    }




}


