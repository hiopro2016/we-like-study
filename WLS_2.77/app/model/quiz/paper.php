<?php
include_once dirname(__FILE__).'/../quiz.php';
include_once dirname(__FILE__).'/../subject.php';
include_once dirname(__FILE__).'/../question.php';
include_once dirname(__FILE__).'/../question/log.php';
include_once dirname(__FILE__)."/../user.php";
include_once dirname(__FILE__).'/../quiz.php';
include_once dirname(__FILE__).'/../quiz/log.php';

include_once dirname(__FILE__).'/../../../libs/phpexcel/Classes/PHPExcel.php';
include_once dirname(__FILE__).'/../../../libs/phpexcel/Classes/PHPExcel/IOFactory.php';
require_once dirname(__FILE__).'/../../../libs/phpexcel/Classes/PHPExcel/Writer/Excel5.php';

class m_quiz_paper extends wls implements dbtable,fileLoad{
	public $phpexcel;
	public $id_paper = null;
	public $questions = null;
	public $paper = null;

	public function insert($data){
		$pfx = $this->cfg->dbprefix;
		$conn = $this->conn();

		$keys = array_keys($data);
		$keys = implode(",",$keys);
		$values = array_values($data);
		$values = implode("','",$values);
		$sql = "insert into ".$pfx."wls_quiz_paper (".$keys.") values ('".$values."')";
		mysql_query($sql,$conn);
		return mysql_insert_id($conn);
	}

	public function delete($ids){
		$pfx = $this->cfg->dbprefix;
		$conn = $this->conn();

		$sql = "delete from ".$pfx."wls_question where id_quiz in (
			select id_quiz from ".$pfx."wls_quiz_paper where id in (".$ids.")
		) ";
		mysql_query($sql,$conn);	
		
		$sql = "delete from ".$pfx."wls_quiz where id in (
			select id_quiz from ".$pfx."wls_quiz_paper where id in (".$ids.")
		) ";
		mysql_query($sql,$conn);
		
		$sql = "delete from ".$pfx."wls_quiz_paper where id in (".$ids.") ";
		mysql_query($sql,$conn);
	}

	public function update($data){
		$pfx = $this->cfg->dbprefix;
		$conn = $this->conn();

		$id = $data['id'];
		unset($data['id']);
		$keys = array_keys($data);

		$sql = "update ".$pfx."wls_quiz_paper set ";
		for($i=0;$i<count($keys);$i++){
			$sql.= $keys[$i]."='".$data[$keys[$i]]."',";
		}
		$sql = substr($sql,0,strlen($sql)-1);
		$sql .= " where id =".$id;
		try{
			mysql_query($sql,$conn);
			return true;
		}catch (Exception $ex){
			return false;
		}
	}

	public function create(){
		$conn = $this->conn();
		$pfx = $this->cfg->dbprefix;

		$sql = "drop table if exists ".$pfx."wls_quiz_paper;";
		mysql_query($sql,$conn);
		$sql = "
			create table ".$pfx."wls_quiz_paper(
				 id int primary key auto_increment	
				,id_quiz int default 0
				
				,money int default 0				
			
			) DEFAULT CHARSET=utf8;
			";
		mysql_query($sql,$conn);
		return true;
	}

	public function importAll($path){}

	public function exportAll($path=null){
		$conn = $this->conn();
		$pfx = $this->cfg->dbprefix;

		$sql = "select id from ".$pfx."wls_quiz_paper; ";
		$res = mysql_query($sql,$conn);
		$ids = '';
		while($temp = mysql_fetch_assoc($res)){
			$ids .= $temp['id'].',';
		}
		$ids = substr($ids,0,strlen($ids)-1);

		return $ids;
	}

	/**
	 * Mainly used in testting, while it's on develop process
	 * Unsuggestable to be used after release.
	 * It need high memory usage.
	 * 
	 * @param path   String ,the Excel file path
	 * @return file   String  
	 * */
	public function importOne($path){
		$conn = $this->conn();
		$pfx = $this->cfg->dbprefix;

		$PHPReader = PHPExcel_IOFactory::createReader('Excel5');
		$PHPReader->setReadDataOnly(true);
		$this->phpexcel = $PHPReader->load($path);

		$currentSheet = $this->phpexcel->getSheetByName($this->il8n['quiz']['paper']);
		$allRow = $currentSheet->getHighestRow();
		$allColmun = $currentSheet->getHighestColumn();

		$quizData = array();
		$paperData = array();
		$quizData['imagePath'] = '';
		for($i='A';$i<=$allColmun;$i++){
			if($currentSheet->getCell($i."1")->getValue()==$this->il8n['normal']['title']){
				$quizData['title'] = $currentSheet->getCell($i."2")->getValue();
			}
			if($currentSheet->getCell($i."1")->getValue()==$this->il8n['user']['money']){
				$paperData['money'] = $currentSheet->getCell($i."2")->getValue();
			}
			if($currentSheet->getCell($i."1")->getValue()==$this->il8n['quiz']['author']){
				$quizData['author'] = $currentSheet->getCell($i."2")->getValue();
			}
			if($currentSheet->getCell($i."1")->getValue()==$this->il8n['quiz']['imagePath']){
				$imagePath = $currentSheet->getCell($i."2")->getValue();
				$quizData['imagePath'] = $imagePath;
			}
			if($currentSheet->getCell($i."1")->getValue()==$this->il8n['subject']['subject']){
				$quizData['id_level_subject'] = $currentSheet->getCell($i."2")->getValue();
				$sql_ = "select name from ".$pfx."wls_subject where id_level = '".$quizData['id_level_subject']."'; ";
				$res = mysql_query($sql_,$conn);
				$temp = mysql_fetch_assoc($res);
				if($temp==false){
					$this->error("quiz_paper::importOne, subject name not found");
					return false;
				}
				$quizData['name_subject'] = $temp['name'];
			}
		}

		$quizObj = new m_quiz();
		$quizData['id'] = $quizObj->insert($quizData);
		if($quizData['id']==false)return false;
		$quizObj->id_quiz = $quizData['id'];
		$quizObj->quizData = $quizData;
		$return = $quizObj->importOne($this->phpexcel);
		if($return==false){
			$this->error("quiz_paper::importOne, quiz::importOne , error");
			return false;
		}

		$paperData['id_quiz'] = $quizData['id'];
		$this->insert($paperData);
		
		return true;
	}

	public function exportOne($path=null){
		$conn = $this->conn();
		$pfx = $this->cfg->dbprefix;

		$sql = "select
			 ".$pfx."wls_quiz_paper.id as pid
			,".$pfx."wls_quiz.id as qid
			,money
			,author
			,id_level_subject
			,title
			,imagePath
			
			from ".$pfx."wls_quiz_paper
			join ".$pfx."wls_quiz 
			on ".$pfx."wls_quiz_paper.id_quiz = ".$pfx."wls_quiz.id 
			where ".$pfx."wls_quiz_paper.id = ".$this->id_paper." ;";

		$res = mysql_query($sql,$conn);
		$temp = mysql_fetch_assoc($res);

		$objPHPExcel = new PHPExcel();
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle($this->il8n['quiz']['paper']);

		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(40);
		$objPHPExcel->getActiveSheet()->setCellValue('A1', $this->il8n['normal']['title']);
		$objPHPExcel->getActiveSheet()->setCellValue('B1', $this->il8n['subject']['subject']);
		$objPHPExcel->getActiveSheet()->setCellValue('C1', $this->il8n['quiz']['author']);

		$objPHPExcel->getActiveSheet()->setCellValue('A2', $temp['title']);
		$objPHPExcel->getActiveSheet()->setCellValue('B2', $temp['id_level_subject']);
		$objPHPExcel->getActiveSheet()->setCellValue('C2', $temp['author']);

		$chr = 66;
		if(!($temp['money']==''||$temp['money']=='0')){
			$chr++;
			$objPHPExcel->getActiveSheet()->setCellValue(chr($chr).'1', $this->il8n['user']['money']);
			$objPHPExcel->getActiveSheet()->setCellValue(chr($chr).'2', $temp['money']);
		}
		$quizObj = new m_quiz();
		if(!($temp['imagePath']==''||$temp['imagePath']=='0')){
			$chr++;
			$objPHPExcel->getActiveSheet()->setCellValue(chr($chr).'1', $this->il8n['quiz']['imagePath']);
			$objPHPExcel->getActiveSheet()->setCellValue(chr($chr).'2', $temp['imagePath']);
			$quizObj->imagePath = $temp['imagePath'];
		}

		$quizObj->exportOne($temp['qid'],$objPHPExcel);
		$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);

		$file =  "download/".date('YmdHis').".xls";
		if($path==null){
			$path = $this->cfg->filePath.$file;
		}
		$objWriter->save($path);
		return $file;
	}

	public function getList($page=null,$pagesize=null,$search=null,$orderby=null,$columns="*"){
		$pfx = $this->cfg->dbprefix;
		$conn = $this->conn();

		$where = " where ".$pfx."wls_quiz_paper.id_quiz = ".$pfx."wls_quiz.id  ";
		$userObj = new m_user();
		$me = $userObj->getMyInfo();
		$temp = explode(',',$me['subjects']);
		$ids = '';
		for($i=0;$i<count($temp);$i++){
			$ids .= "'".$temp[$i]."',";
		}
		$ids = substr($ids,0,strlen($ids)-1);
		$where .= " and id_level_subject in (".$ids.") ";

		if($search!=null){
			$keys = array_keys($search);
			for($i=0;$i<count($keys);$i++){
				if($keys[$i]=='id'){
					$where .= " and ".$pfx."wls_quiz_paper.id in (".$search[$keys[$i]].") ";
				}
				if($keys[$i]=='title'){
					if(count($search[$keys[$i]])==1){
						$where .= " and ".$pfx."wls_quiz.title like '%".$search[$keys[$i]][0]."%' ";
					}else{
						$where .= " and (";
						for($i2=0;$i2<count($search[$keys[$i]]);$i2++){
							$where .= " ".$pfx."wls_quiz.title like '%".$search[$keys[$i]][$i2]."%' or";
						}
						$where = substr($where,0,strlen($where)-2);
						$where .= " ) ";
					}
				}else if($keys[$i]=='money'){
					if(count($search[$keys[$i]])==1){
						$where .= " and ".$pfx."wls_quiz_paper.money ".$search[$keys[$i]][0][0]." ".$search[$keys[$i]][0][1]." ";
					}else{
						$where .= " and (";
						for($i2=0;$i2<count($search[$keys[$i]]);$i2++){
							$where .= " ".$pfx."wls_quiz_paper.money ".$search[$keys[$i]][$i2][0]." ".$search[$keys[$i]][$i2][1]." or";
						}
						$where = substr($where,0,strlen($where)-2);
						$where .= " ) ";
					}
				}else if($keys[$i]=='subject'){
					if(count($search[$keys[$i]])==1){
						$sql_subject = "select id_level from ".$pfx."wls_subject where name = '".$search[$keys[$i]][0][1]."' ;";
						$res_subject = mysql_query($sql_subject,$conn);
						$temp_subject = mysql_fetch_assoc($res_subject);

						$where .= " and ".$pfx."wls_quiz.id_level_subject = ".$temp_subject['id_level']." ";
					}else{
						$name_subjects = '';
						for($i2=0;$i2<count($search[$keys[$i]]);$i2++){
							$name_subjects .= "'".$search[$keys[$i]][$i2][1]."',";
						}
						$name_subjects = substr($name_subjects,0,strlen($name_subjects)-1);
						$sql_subject = "select id_level from ".$pfx."wls_subject where name in (".$name_subjects.") ;";
						$res_subject = mysql_query($sql_subject,$conn);
						$ids_subject = '';
						while($temp_subject = mysql_fetch_assoc($res_subject)){
							$ids_subject .= "'".$temp_subject['id_level']."',";
						}
						$ids_subject = substr($ids_subject,0,strlen($ids_subject)-1);

						$where .= " and ".$pfx."wls_quiz.id_level_subject in (".$ids_subject.")  ";
					}
				}else if($keys[$i]=='id_level_subject'){
					//print_r($search);
					$where .= " and ".$pfx."wls_quiz.id_level_subject = '".$search[$keys[$i]][0][1]."'";
				}
			}
		}
		if($orderby==null)$orderby = " order by ".$pfx."wls_quiz_paper.id ";
		$sql = "select 
			 ".$pfx."wls_quiz_paper.id
			,".$pfx."wls_quiz_paper.id_quiz
			,".$pfx."wls_quiz_paper.money
			
			,".$pfx."wls_quiz.title
			,".$pfx."wls_quiz.name_subject
			,".$pfx."wls_quiz.id_level_subject
			,".$pfx."wls_quiz.ids_questions
			,".$pfx."wls_quiz.count_used
			,".$pfx."wls_quiz.date_created
			,".$pfx."wls_quiz.score_top
			,".$pfx."wls_quiz.score_avg

		from ".$pfx."wls_quiz_paper , ".$pfx."wls_quiz ".$where." ".$orderby;
		$sql .= " limit ".($pagesize*($page-1)).",".$pagesize." ";

		$res = mysql_query($sql,$conn);
		$arr = array();
		$index = 1;
		while($temp = mysql_fetch_assoc($res)){
			$temp['index'] = $index;
			$index ++;
			if(isset($temp['date_created'])){
				$temp['date_created2'] = $this->tool->getTimeDif($temp['date_created']);
			}
			$arr[] = $temp;
		}

		$sql2 = "select count(".$pfx."wls_quiz_paper.id) as total from ".$pfx."wls_quiz_paper , ".$pfx."wls_quiz   ".$where;
		$res = mysql_query($sql2,$conn);
		$temp = mysql_fetch_assoc($res);
		$total = $temp['total'];

		return array(
			'page'=>$page,
			'data'=>$arr,
			'sql'=>$sql,
			'total'=>$total,
			'pagesize'=>$pagesize,
			'search'=>json_encode($search)
		);
	}

	public function getQuizIds(){
		$pfx = $this->cfg->dbprefix;
		$conn = $this->conn();

		$sql = "select questions,id from ".$pfx."wls_quiz_paper where id = ".$this->id;
		$res = mysql_query($sql,$conn);
		$temp = mysql_fetch_assoc($res);
		return $temp['questions'];
	}

	public function checkMoney($id){
		$pfx = $this->cfg->dbprefix;
		$conn = $this->conn();

		$sql = "select money,id from ".$pfx."wls_quiz_paper where id= ".$id;
		$res = mysql_query($sql,$conn);
		$temp = mysql_fetch_assoc($res);
		if($temp['money']==0)return true;

		$userObj = new m_user();
		$user = $userObj->getMyInfo();
		$sql = "select * from ".$pfx."wls_user where id = ".$user['id'];
		$res = mysql_query($sql,$conn);
		$user = mysql_fetch_assoc($res);

		if(intval($temp['money'])>0 && $user['username']=='guest')return false;
		if(intval($user['money'])>=intval($temp['money'])){
			$sql = "update ".$pfx."wls_user set money = money - ".$temp['money']." where id = ".$user['id'];

			if(!isset($_SESSION)){
				session_start();
			}
			$_SESSION['wls_user']['money'] -= $temp['money'];
			mysql_query($sql,$conn);

			if($this->cfg->cmstype!='' && $user['username']!='guest' ){
				$obj = null;
				eval("include_once dirname(__FILE__).'/../integration/".$this->cfg->cmstype.".php';");
				eval('$obj = new m_integration_'.$this->cfg->cmstype.'();');
				$obj->synchroMoney($user['username']);
			}
			return true;
		}else{
			return false;
		}
	}

	//TODO
	public function checkMyPaper($answers,$paperid){
		$pfx = $this->cfg->dbprefix;
		$conn = $this->conn();
		$sql = "select * from ".$pfx."wls_quiz_paper where id=".$paperid;
		$res = mysql_query($sql,$conn);
		$temp = mysql_fetch_assoc($res);
		$id_quiz = $temp['id_quiz'];

		$ques_ = array();
		$ids_question = '';
		for($i=0;$i<count($answers);$i++){
			$ques_[$answers[$i]['id']] = $answers[$i]['answer'];
			$ids_question .= $answers[$i]['id'].",";
		}
		$ids_question = substr($ids_question,0,strlen($ids_question)-1);

		//It's written in controller/quiz.php
		$questionObj = new m_question();
		$answers = $questionObj->getAnswers($ques_);

		//Just out put the answsers if the current user is guest, and do nothing
		$userObj = new m_user();
		$user = $userObj->getMyInfo();
		if($user['username']=='guest'){
			return $answers;
		}

		//Do quiz log.
		$quizLogObj = new m_quiz_log();
		$quizLogObj->addLog(array(
			 'id_quiz'=>$temp['id_quiz']
			,'answers'=>$answers
			,'ids_question'=>$ids_question
		));

		return $answers;
	}
}
?>