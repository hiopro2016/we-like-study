<?php
class subject extends wls{
	
	private $m = null;
	
	function subject(){
		parent::wls();
		include_once $this->c->license.'/model/subject.php';
		$this->m = new m_subject();
	}
	
	public function jsonList(){
		$page = 1;
		if(isset($_POST['start']))$page = ($_POST['start']+$_POST['limit'])/$_POST['limit'];
		$pagesize = 15;
		if(isset($_POST['limit']))$pagesize = $_POST['limit'];
		$data = $this->m->getList($page,$pagesize);
		$data['totalCount'] = $data['total'];
		echo json_encode($data);
	}
	
	public function viewUpload(){
		echo '
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<html xmlns="http://www.w3.org/1999/xhtml">		
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				</head>
				<body>
					导入EXCEL
					<form action="wls.php?controller=subject&action=saveUpload" method="post"
					enctype="multipart/form-data">
						<label for="file">EXCEL文件:</label>
						<input type="file" name="file" id="file" />
						<br />
						<input type="submit" name="submit" value="提交" />
					</form>
				</body>
			</html>		
		';
	}
	
	public function saveUpload(){
		if ($_FILES["file"]["error"] > 0){
			echo "Error: " . $_FILES["file"]["error"] . "<br />";
		}else{
			move_uploaded_file($_FILES["file"]["tmp_name"],dirname(__FILE__)."/../../../file/upload/". $_FILES["file"]["name"]);
			$this->m->create();
			$this->m->importExcel(dirname(__FILE__)."/../../../file/upload/". $_FILES["file"]["name"]);
		}
		echo '已经导入';
	}	
	
	public function saveUpdate(){
		$data = array(
			'id'=>$_POST['id'],
			$_POST['field']=>$_POST['value']
		);
		if($this->m->update($data)){
			echo "已经更新";
		}else{
			echo "更新失败";			
		}
	}
	
	public function viewExport(){
		$file = $this->m->exportExcel();
		echo "<a href='/".$file."'>下载</a>";
	}
	


	public function delete(){
		if($this->m->delete($_POST['id'])){
			echo '操作成功';
		}else{
			echo '操作失败';
		}
	}
	
	public function getPaperList(){
		include_once dirname(__FILE__).'/../model/quiz/paper.php';		
		$paper = new m_quiz_paper();
				
		$page = 1;
		if(isset($_POST['start']))$page = ($_POST['start']+$_POST['limit'])/$_POST['limit'];
		$pagesize = 15;
		if(isset($_POST['limit']))$pagesize = $_POST['limit'];
		$data = $paper->getList($page,$pagesize,array('id_level_subject'=>"'".$_REQUEST['id_level_subject']."'"));
		$data['totalCount'] = $data['total'];
		echo json_encode($data);
	}
	
	public function getMyQuizLine(){
		include_once dirname(__FILE__).'/../model/quiz/log.php';		
		$log = new m_quiz_log();
		$user = $this->getMyUser();
		$search = array('id_user'=>$user['id']);
		if(isset($_REQUEST['id_level_subject_']) && $_REQUEST['id_level_subject_']!=''){
			$search['id_level_subject_'] = $_REQUEST['id_level_subject_'];
		}
		$data = $log->getList(1,100,$search);
		$data = $data['data'];
		$arr = array();
		for($i=0;$i<count($data);$i++){
			$arr [] = array(
				'index' => $i+1,
				'proportion'=>floor((100*$data[$i]['count_right']) /($data[$i]['count_right'] + $data[$i]['count_wrong'])) 
			);
		}

		$xml = '
<?xml version="1.0" encoding="UTF-8"?>
<settings> 
	


  <background>
    <color>#EEEEEE</color>
    <alpha>100</alpha>
    <border_color>#999999</border_color>
    <border_alpha>90</border_alpha>
  </background>	
  
  <values>
  	<y_left> 
  		<max>100</max>
  		<min>0</min>
  	</y_left>  
  </values>
  
   <plot_area>  
	<margins>                                                
      <left>30</left>                                          
      <top>10</top>                                             
      <right>0</right>                                        
      <bottom>0</bottom>                                       
    </margins>
 <legend>
 <enabled>false</enabled>
   </legend> 
   </plot_area>  


   
	<graphs>
		<graph gid="1">   
		 	<axis>left</axis>                                      
			                 
			<color>#999999</color>                                                            
			<fill_alpha>50</fill_alpha>    
                         
     	</graph>  	
     </graphs>   
	<data> 
	<chart>
	<series>';
	for($i=0;$i<count($arr);$i++){
		$xml .= '<value xid="'.$i.'">'.$arr[$i]['index'].'</value>';	
	}
	$xml .='
	</series>

	<graphs>
		<graph gid="1">';
	for($i=0;$i<count($arr);$i++){
		$xml .= '<value xid="'.$i.'">'.$arr[$i]['proportion'].'</value>';	
	}
	$xml .= '

		</graph>

		

	</graphs>
	<guides>	                                   
	 <guide>                                     
	   <start_value>60</start_value>               
	   <title>及格线60</title>                           
	   <color>#00CC00</color>                             
	   <inside>true</inside>                         
	 </guide>  
	</guides>	
</chart>
	
	</data>
		';
		
		echo $xml;		
		


		
		
	}
}
?>