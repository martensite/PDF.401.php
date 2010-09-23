<?php
$all_language=array('tw'=>0,'cn'=>1,'en'=>2,'jp'=>3);
if(!isset($lang)) $lang='tw';
if(!isset($ui_lang)) $ui_lang='tw';
define(LANGUAGE,$all_language[(($lang=='multi')?$ui_lang:$lang)]);

function my_number_format($num){
	$tmp=explode('.',$num); 
  	$out=number_format($tmp[0],0,'.',','); 
  	if(isset($tmp[1])){
  		$tmp[1]=preg_replace('/0+$/','',$tmp[1]); 
  		if(strcmp($tmp[1],'')!=0)
  			$out.='.'.$tmp[1];
	}
	return $out;
}
include_once('pdf/jpdf.php');
/*
switch($lang){
	case 'en':
		include_once('pdf/fpdf.php');
		class basePDF extends FPDF{
			function AddMyFont(){}
			function SetMyFont($family,$style='',$size=0){
//				$this->SetFont(($family=='my')?'Arial':'');	
//				if($size) $this->SetFontSize($size);
				$this->SetFont((($family=='my')?'Arial':''),$style,$size);
			}
			function convFont($str){return $str;}
			function replaceTotalPageNum(){}
		}
		break;
	
	
		include_once('pdf/chinese.php');
		class basePDF extends PDF_Chinese{
			function AddMyFont(){$this->AddBig5hwFont('my');} // 有些字型英文會漏 ` 及 ' 符號, 改用hw字型就ok, 有些換了也沒用
			function SetMyFont($family,$style='',$size=0){$this->SetFont($family,$style,$size);}
			function convFont($str){return iconv("UTF-8","BIG5",$str);}
			function replaceTotalPageNum(){}
		}
		break;
	case 'cn':
		include_once('pdf/chinese.php');
		class basePDF extends PDF_Chinese{
			function AddMyFont(){$this->AddGBhwFont('my');}
			function SetMyFont($family,$style='',$size=0){$this->SetFont($family,$style,$size);}
			function convFont($str){return iconv("UTF-8","GB18030",$str);} // GB18030 是最新的汉字编码字符集国家标准, 向下兼容GBK和GB2312标准
			function replaceTotalPageNum(){}
		}
		break;
	case 'jp':
		include_once('pdf/japanese.php');
		class basePDF extends PDF_Japanese{
			function AddMyFont(){$this->AddSJIShwFont('my');}
			function SetMyFont($family,$style='',$size=0){$this->SetFont($family,$style,$size);}
			function convFont($str){return iconv("UTF-8","SJIS",$str);}
			function replaceTotalPageNum(){}
		}
		break;
	case 'multi':
		include_once('pdf/chinese-unicode.php');		
		class basePDF extends PDF_Unicode{
			function AddMyFont(){$this->AddUniGBhwFont('my','AdobeSongStd-Light-Arco');}
			function SetMyFont($family,$style='',$size=0){$this->SetFont($family,$style,$size);} 
			function convFont($str){return $str;}

			function replaceTotalPageNum(){
			  if(!empty($this->AliasNbPages)){ //Replace number of pages
			    $cs=preg_split("//",$this->page.'');
			    $nb_hex='';
			    foreach($cs as $c)
			      if(strcmp($c,'')!=0)
			        $nb_hex.='00'.dechex(ord($c));      
			
			    $cs=preg_split("//",$this->AliasNbPages);
			    $AliasNbPages='';
			    foreach($cs as $c)
			      if(strcmp($c,'')!=0)
			        $AliasNbPages.='00'.dechex(ord($c));
			    
				if($this->state<3)
					$this->Close();
//			    for($n=0;$n<=$this->page;$n++)
//			      $this->pages[$n]=str_replace($AliasNbPages,$nb_hex,$this->pages[$n]);
				$this->buffer=str_replace($AliasNbPages,$nb_hex,$this->buffer);
			  }
			}
		}
		break;
}
*/

include('/data/Cyberhood/BrowserUI/tw/crm/pdfTerms.msg'); // 多語名詞定義
//require_once('erp_rpc/Common.ERPUtility.php');
require_once('Common.ERPUtility.php');
require_once('common.Utility.php');

/*
// --------------------
// 讀取報價單內容
//	'quotation_view.php?'SQ_NO='+sq_no+'&C_NO='+erp_branches[0].no+'&auth_key='+erp_branches[0].key+'&u_str='+getCookieValue("simple_u_str")

	$dl=(isset($dl)&&$dl==1)?true:false;

	if(!isset($C_NO))  // 公司別
		die('Need C_NO!');
	if(!isset($SQ_NO))  // 報價單號
		die('Need SQ_NO!');
	if(!isset($auth_key))
		die('Need auth_key!');
	if(!isset($u_str))
		die('Need u_str!');

	$u_str=kwut3_decryptuserstring($u_str, "/usr/local/koala/config.ini");
	$uid=secr_getuserid($u_str);

	if(!crm_check_right($auth_key, $C_NO, $uid, 'Order', 'R', null))
		die("Permission denied!\nYou have no right to access this quotation!");


	if(!$__db=connectdb($auth_key))
		die('Connecting db failed!');

	// 公司別基本資料
	$r=read_one_record($__db, "select C_WNAME,C_ADDR,C_TEL,C_FAX,C_LOGO,C_LOGO_EXT,C_CITY,C_ZIPCODE,C_STATE,C_COUNTRY from SYS_COMPANY where C_NO=?", array($C_NO));
	if($r===false){
		echo kwcr2_geterrormsg($__db, 1);
		kwcr2_unmapdb($__db);
		exit;
	}else if(!isset($r)){
		echo "Company Information not found!(no. $C_NO)";
		kwcr2_unmapdb($__db);
		return;
	}else{
		$info=array('wname'=>$r[0], 'addr'=>$r[1], 'tel'=>$r[2], 'fax'=>$r[3],
					'logo'=>($r[4]!='<BLOB>:NULL')?$r[4]:'', // '/tmp/cbtemp/kd1/20081208/l5/c0/MUhUot.5.39772069'
					'ext'=>$r[5], 'city'=>$r[6], 'zipcode'=>$r[7], 'state'=>$r[8], 'country'=>$r[9]);
	}

	// 交易條件代碼意義
	$TRADEMENT=array();
	$rs=read_multi_record($__db, "select TD_NO,TD_DESC from TRADEMENT where C_NO=? and TD_TYPE=?", array($C_NO,'S'));
	if($rs===false){
		echo "Fail to get enough information for this quotation!\n",kwcr2_geterrormsg($__db, 1);
		kwcr2_unmapdb($__db);
		exit;
	}else{
		foreach($rs as $r)
			$TRADEMENT[$r[0]]=$r[1];
	}
	// 付款條件代碼意義
	$PAYTERM=array();
	$rs=read_multi_record($__db, "select PA_NO,PA_DESC from PAYTERM where C_NO=? and PA_TYPE=?", array($C_NO,'S'));
	if($rs===false){
		echo "Fail to get enough information for this quotation!\n",kwcr2_geterrormsg($__db, 1);
		kwcr2_unmapdb($__db);
		exit;
	}else{
		foreach($rs as $r)
			$PAYTERM[$r[0]]=$r[1];
	}
	// 運送方式代碼意義
	$VIA_LIST=array();
	$rs=read_multi_record($__db, "select VIA_NO,VIA_DESC from VIA_LIST where C_NO=? and VIA_TYPE=?", array($C_NO,'S'));
	if($rs===false){
		echo "Fail to get enough information for this quotation!\n",kwcr2_geterrormsg($__db, 1);
		kwcr2_unmapdb($__db);
		exit;
	}else{
		foreach($rs as $r)
			$VIA_LIST[$r[0]]=$r[1];
	}
// CUSTOMER_ADDR	
$sql=<<<EOF
select SQ_NO,SQ_DATE,SQ_ATTN,SQ_CU_TEL,SQ_CU_FAX,SQ_DT_NO,SQ_AMOUNT,SQ_EM_NAME,SQ_LOCATION,SQ_MEMO,
SQ_PA_NO,SQ_TAX,SQ_TAX_DESC,SQ_TAX_RATE,SQ_STATUS,
SQ_CU_NO,SQ_TRADETERM,SQ_VIA_NO,SQ_EXP_DATE,SQ_U_ID,
SQ_CU_EMAIL,SQ_EM_TEL,SQ_EM_FAX,CUA_ADDR from SALE_Q where C_NO=? and SQ_NO=?
EOF;
	$p=array($C_NO,$SQ_NO);
	$r=read_one_record($__db, $sql, $p);
	if($r===false){
		echo kwcr2_geterrormsg($__db, 1);
		kwcr2_unmapdb($__db);
		exit;
	}else if(!isset($r)){
		echo "Quotation not found!(no. $SQ_NO)";
		kwcr2_unmapdb($__db);
		return;
	}else{
		$quotation=array('SQ_NO'=>$r[0],'SQ_DATE'=>$r[1],'SQ_ATTN'=>$r[2],'SQ_CU_TEL'=>$r[3],'SQ_CU_FAX'=>$r[4],
						'SQ_DT_NO'=>$r[5],'SQ_AMOUNT'=>$r[6],'SQ_EM_NAME'=>$r[7],'SQ_LOCATION'=>$r[8],'SQ_MEMO'=>$r[9],
						'SQ_PA_NO'=>$r[10],'SQ_TAX'=>$r[11],'SQ_TAX_DESC'=>$r[12],'SQ_TAX_RATE'=>$r[13],'SQ_STATUS'=>$r[14],
						'SQ_CU_NO'=>$r[15],'SQ_TRADETERM'=>$r[16],'SQ_VIA_NO'=>$r[17],'SQ_EXP_DATE'=>$r[18],
						'SQ_U_ID'=>$r[19],'SQ_CU_EMAIL'=>$r[20],'SQ_EM_TEL'=>$r[21],'SQ_EM_FAX'=>$r[22],'SQ_CUA_ADDR'=>$r[23],'ITEMS'=>array());
	}
	$sql="select C.CU_WNAME,C.CU_UNIONID,A.CUA_ADDR from CUSTOMER C";
	$sql.=" left outer join CUSTOMER_ADDR A ON C.C_NO=A.C_NO and C.CU_NO=A.CU_NO";
	$sql.=" where C.C_NO=? and C.CU_NO=?";
	$r=read_one_record($__db, $sql, array($C_NO,$quotation['SQ_CU_NO']));
	if($r===false){
		echo kwcr2_geterrormsg($__db, 1);
		kwcr2_unmapdb($__db);
		exit;
	}else if(!isset($r)){
		echo "Customer not found!(no. ".$quotation['SQ_CU_NO'].")";
		kwcr2_unmapdb($__db);
		return;
	}else{
		$quotation['CU_WNAME']=$r[0];
		$quotation['CU_UNIONID']=$r[1];
		$quotation['CUA_ADDR']=$r[2];
 	}
	
	// detail
	$sql="select SQ_ITEM,SQ_MA_NO,SQ_MA_DESC,SQ_MA_SPEC,SQ_UNIT_QTY,SQ_MA_UNIT,SQ_UPRICE,SQ_AMT,SQ_QTY,SQ_DT_MEMO,SQ_CU_MA from SALE_Q_DT where C_NO=? and SQ_NO=?";
	$p=array($C_NO,$SQ_NO);
	$rs=read_multi_record($__db, $sql, $p);
	if($rs===false){
		echo kwcr2_geterrormsg($__db, 1);
		kwcr2_unmapdb($__db);
		exit;
	}else{ 
		// 成威客製, 報價單需顯示料品的衛署字號,健保碼
		foreach($rs as $r){
			$r0=read_one_record($__db, "select MA_RESERVE1,MA_RESERVE2 from MATERIAL where C_NO=? and MA_NO=?" , array($C_NO,$r[1]));
			if($r0===false||!isset($r0)){
				$MA_RESERVE1='     ';
				$MA_RESERVE2='     ';
			}else{
				$MA_RESERVE1=(strcmp($r0[0],'')==0)?'     ':$r0[0];
				$MA_RESERVE2=(strcmp($r0[1],'')==0)?'     ':$r0[1];
			}
			$quotation['ITEMS'][]=array('SQ_MA_NO'=>$r[1],'SQ_MA_DESC'=>$r[2],'SQ_UNIT_QTY'=>$r[4],'SQ_MA_UNIT'=>$r[5],'SQ_UPRICE'=>$r[6],
									'SQ_MA_SPEC'=>$r[3],'SQ_AMT'=>$r[7],'SQ_DT_MEMO'=>$r[9],
									'EXTRA_INFO1'=>$MA_RESERVE1,	// 衛署字號
									'EXTRA_INFO2'=>$MA_RESERVE2); 	// 健保碼
		}
	}
	
	disconnectdb($__db);
// -----------------------------------------------

           */





class myPDF extends basePDF{
	var $TERMS; // 0報價單,1業務人員,2報價單號,3報價日期,4有效日期,5頁次,6客戶名稱,7聯絡人,8交易條件,9幣別,10稅別,11付款條件,12運送方式,13免稅,14零稅,15外加,16內含,17料品編號,18料品名稱/規格,19數量,20單位,21單價,22金額,23備註,24未稅金額,25稅額,26合計金額,27客戶簽名
	function Header(){
    //Select Arial bold 15
    $this->SetFont('my','B',8);
    //Move to the right

    //Line break
	}
	function Footer(){
	  $left=25;
		$this->SetMyFont('my','B',10);
		$this->SetXY(20,240);		
		$this->Cell(60,5,$this->convFont('紙張尺度(350 ×250)公厘'),0,0,'L');
	}
	function checkPage($isDetail=false){
		global $vertical_points;
		if($this->GetY()>260){
			if($isDetail){
				$this->SetMyFont('my','I',7);
				$this->Cell(0,4,'Continued on next page....',0,1,'C');
			}
			$this->AddPage();
			$this->SetY($this->GetY()+11);
			if($isDetail){
				$vertical_points=array($this->GetY(),0,0);
				($this); 
			}
		}
	}
}

function buildCenterInfo(&$pdf){
  $center_top="（一般稅額計算 － 專營應稅營業人使用）";
  $center_top.="\n所屬年月份：　　　年　　－　　月　             金額單位：新臺幣元";
  return $center_top; 
}

function buildRightInfo(&$pdf){ 
	$right='核准按月申報';	//$to.="\n".$pdf->data['CU_WNAME'];
	$right.="\n總機構彙總申報";
	$right.="\n各單位分別申報" ;
	return $right;
}

function Master(&$pdf){
	global $vertical_points;
	global $TRADEMENT,$PAYTERM,$VIA_LIST;
	$pdf->SetMyFont('my','B',8);
    
  $cell_size=17.3;
  $table_size=259.9;	
	$size_x=350;
	$size_y=250;
	$cell_height=10;
	$top=20;
	$left=20;
	$right=20;
	$bottom=20;
	$center=175;
	$cw=array(10,45,20,40,7,20);
	$n=count($cw);
	$rw=array(10,5,12.5,7.5);
	//draw left-top
	$pdf->SetXY($left,$top);
  $top_left=array('統 一 編 號','營業人名稱','稅 籍 編 號');
  foreach($top_left as $value){
    $pdf->setX($left);
    $pdf->cell($cw[2],$rw[0],$pdf->convFont($value),1,1,'C');
  }
  $line=3;
  $now_y=$left+$rw[0]*$line;
  $pdf->setXY($left,$now_y);
  $company=array('負責人姓名','','營業地址','   ','使用發票份數','                     份');

  $cw1=array(20,40,20,160,20,50);
  foreach($company as $key=>$col)
  {    
    if($key==3)
        $pdf->SetFont('my','B',6);
    if($key==4)
        $pdf->SetFont('my','B',8);
    $pdf->Cell($cw1[$key],$rw[0],$pdf->convFont($col),1,0,'C');        
  }  
  //address detail
  $c=array("縣\n市","鄉鎮\n市區","路\n街","段","巷","弄","號","樓","室");
  $pdf->SetFont('my','B',6);
  $now_x=$left+$cw1[0]+$cw[1]+$cw[2];
  $pdf->setX($now_x);
  foreach($c as $key=>$value){
    if($key==0 || $key==1 ||$key==2){
    $pdf->setXY($now_x+($key+0.7)*($cw1[3]/9),$now_y+3);
     $pdf->multicell($cw1[3]/10,2,$pdf->convFont($value),0,'L'); 
    }      
    else{
      $pdf->setXY($now_x+($key+0.5)*($cw1[3]/9),$now_y);
      $pdf->Cell($cw1[3]/10,$rw[0],$pdf->convFont($value),0,0,'L');
    }   
  }
  $pdf->SetFont('my','B',8); 

	//8 cells 統 一 編 號
	$pdf->setXY($left+$cw[2],$top);
  $blank=array('','','','','','','','');

	foreach($blank as $value){
    $pdf->cell($cw[1]/8,$rw[0],$value,1,'L');
  }
  
  
  //left-top: 營業人名稱  1 cell
  $pdf->setXY($left+$cw[2],$top+$rw[0]);
  $pdf->cell($cw[1],$rw[0],' ',1,1,'C');
  //稅籍編號  8 cells
	$pdf->setXY($left+$cw[2],$top+2*$rw[0]);
  $blank=array('','','','','','','','');
	foreach($blank as $value){
    $pdf->cell($cw[1]/8,$rw[0],$value,1,'L');
  }  
 
  $line=4;
  $top_left=array('銷','','','','項','進','','','','','','','項');
  $pdf->setXY($left,$top+$rw[0]*$line);
	foreach($top_left as $key=> $value){
	  $pdf->setX($left);
	 if($key==4)
    $pdf->cell($cw[0],$rw[2],$pdf->convFont($value),'LBR',1,'C');
   else	   
    $pdf->cell($cw[0],$rw[0],$pdf->convFont($value),'LR',1,'C');
  } 
  $pdf->setXY($left+$cw[0],$top+$rw[0]*$line);
  $pdf->cell($cw[1],$rw[0],$pdf->convFont('區 分'),'LRT',1,'R');
  $pdf->line($left+$cw[0],$top+$rw[0]*$line,$left+$cw[0]+$cw[1],$top+$rw[0]*$line+$rw[0]);
  $pdf->setXY($left+$cw[0],$top+$rw[0]*$line);
  $pdf->cell($cw[1],$rw[0],$pdf->convFont('項 目'),'LR',0,'L');
  
  $cw2=array(80,40,40,40);
  $pdf->cell($cw2[0],$rw[1],$pdf->convFont('應            稅'),1,1,'C');
  $pdf->setXY($left+$cw[0]+$cw[1],$top+$rw[0]*$line+$rw[1]);
  $pdf->cell($cw2[1],$rw[1],$pdf->convFont('銷　　售　　額'),1,0,'C');
  $pdf->cell($cw2[2],$rw[1],$pdf->convFont('稅　　　　   額'),1,1,'C');
  $pdf->setXY($left+$cw[0]+$cw[1]+$cw2[0],$top+$cell_height*$line);
  $pdf->cell($cw2[3],$rw[0],$pdf->convFont('零稅率銷售額'),1,1,'C');
  $now_x=$left+$cw[0]+$cw[1]+$cw2[0];
  $pdf->setX($left+$cw[0]+$cw[1]+$cw2[0]);
  $pdf->SetFont('my','B',6);
  $cw3=array(8,4);
  $pdf->cell($cw2[3],$rw[1],$pdf->convFont('3(非經海關出口應附證明文件者)'),1,1);
  //8 cells
  $a=array(7,'','','','','','','','');
  //零稅率銷售額 from database
  foreach($a as $key=>$value){
    if($key==0){
      $pdf->setX($now_x);  
      $pdf->cell($cw3[0],$rw[1],$pdf->convFont($value),'TBL',0);      
    }
    elseif($key==2 || $key==5 || $key==8){
      $pdf->setX($now_x+$cw3[0]+($key-1)*$cw3[1]);
      $pdf->cell($cw3[1],$rw[1],$pdf->convFont($value),'TBR',0); 
    }
    else{  
      $pdf->setX($now_x+$cw3[0]+($key-1)*$cw3[1]);  
      $pdf->cell($cw3[1],$rw[1],$pdf->convFont($value),'TB',0);
    }
  }
  //虛線 of 零稅率銷售額 :7非經海關
  //change line style to dash line
  $linestyle=array('dash'=>'3,3');
  $pdf->SetLineStyle($linestyle);
  $step=array(0,1,3,4,6,7);
  foreach($step as $value){
    $now_step=$now_x+$cw3[0];
    $pdf->Line($now_step+$cw3[1]*$value,$now_y+5*$rw[1],$now_step+$cw3[1]*$value,($now_y+5*$rw[1])+$rw[1],$linestyle); 
  }
  //change linestyle to default
  $linestyle=array('dash'=>'0');
  $pdf->SetLineStyle($linestyle); 
  
  $pdf->Ln();
  $pdf->setX($now_x);  
  $pdf->cell($cw2[3],$rw[1],$pdf->convFont('11(經海關出口免附證明文件者)'),1,1);
  //經海關出口 from databash
  $a=array(15,'','','','','','','','');
  foreach($a as $key=>$value){ 
    if($key==0){
      $pdf->setX($now_x); 
      $pdf->cell($cw3[0],$rw[1],$pdf->convFont($value),'TBL',0);
    }
    elseif($key==2 || $key==5 || $key==8){  
      $pdf->setX($now_x+$cw3[0]+($key-1)*$cw3[1]);  
      $pdf->cell($cw3[1],$rw[1],$pdf->convFont($value),'TBR',0);
    }
    else{  
      $pdf->setX($now_x+$cw3[0]+($key-1)*$cw3[1]);  
      $pdf->cell($cw3[1],$rw[1],$pdf->convFont($value),'TB',0);
    }
  }
  //dash of 15,19,23
  $linestyle=array('dash'=>'3,3');
  $pdf->SetLineStyle($linestyle);
  $step=array(0,1,3,4,6,7);
  foreach($step as $value){
    $now_step=$now_x+$cw3[0];   
    $now_y_step=$now_y+7*$rw[1];
    $pdf->Line($now_step+$cw3[1]*$value,$now_y_step,$now_step+$cw3[1]*$value,$now_y_step+3*$rw[1],$linestyle); 
  } 
    //change linestyle to default
  $linestyle=array('dash'=>'0');
  $pdf->SetLineStyle($linestyle); 
    
  $pdf->Ln();
  $a=array(19,'','','','','','','','');
  foreach($a as $key=>$value){
    if($key==0){
      $pdf->setX($now_x); 
      $pdf->cell($cw3[0],$rw[1],$pdf->convFont($value),'LTB',0);
    }
    elseif($key==2 || $key==5 || $key==8){
      $pdf->setX($now_x+$cw3[0]+($key-1)*$cw3[1]);  
      $pdf->cell($cw3[1],$rw[1],$pdf->convFont($value),'TBR',0);    
    }
    else{  
      $pdf->setX($now_x+$cw3[0]+($key-1)*$cw3[1]);  
      $pdf->cell($cw3[1],$rw[1],$pdf->convFont($value),'TB',0);
    }
  }
  
  
  $pdf->Ln();
  $a=array(23,'','','','','','','','');
  foreach($a as $key=>$value){
    if($key==0){
      $pdf->setX($now_x); 
      $pdf->cell($cw3[0],$rw[1],$pdf->convFont($value),'LTB',0);
    }  
    elseif($key==2 || $key==5 || $key==8){  
      $pdf->setX($now_x+$cw3[0]+($key-1)*$cw3[1]);  
      $pdf->cell($cw3[1],$rw[1],$pdf->convFont($value),'TBR',0);
    }             
    else{  
      $pdf->setX($now_x+$cw3[0]+($key-1)*$cw3[1]);  
      $pdf->cell($cw3[1],$rw[1],$pdf->convFont($value),'TB',0);
    }
  }     
  $pdf->SetFont('my','B',8);
  
    
  $line=5;
  $pdf->setXY($left+$cw[0]+$cw[1],$top+$rw[0]*$line);
  $cw3=array(5,3);
  //銷售額:1:三聯式發票  5:收銀機發票  9:二聯式發票   13:免用發票
  //17:退回、折讓   21:合計
  $a=array(1,5,9,13,17,21);
  foreach($a as $key=> $value){
      $pdf->cell($cw3[0],$rw[1],$pdf->convFont($value),1,1,'L');
      $pdf->setX($left+$cw[0]+$cw[1]);
    }
  

  
  $now_y=$pdf->getY();
  $pdf->cell(30,$rw[2],$pdf->convFont('25        元('),0,0);
  $pdf->cell(30,$rw[2],$pdf->convFont('元('),0,0,'L');
  $pdf->multicell(20,5,$pdf->convFont("內含銷售\n固定資產"),0,0);
  $pdf->setXY($left+$cw[0]+$cw[1]+40+20,$now_y);
  $pdf->cell(40,$rw[2],$pdf->convFont('27　　　　　　元）'),0);
  $now_x=$left+$cw[0]+$cw[1]+$cw3[0];
  $now_y=$top+$rw[0]*$line;
  $pdf->setXY($now_x,$now_y);
  //應稅：銷售額 from database
  for($j=0;$j<=10;$j++){
    for($i=0;$i<6;$i++)  
    {
        /*
        if($j==1 || $j ==4 || $j==8){
          $pdf->setXY($now_x+$j*$cw3[1],$now_y+$i*$rw[1]);
          $pdf->cell($cw3[1],$rw[1],$pdf->convFont(''),'TBR',1);
        }
        
        else{
        */
          $pdf->setXY($now_x+$j*$cw3[1],$now_y+$i*$rw[1]);
          $pdf->cell($cw[1],$rw[1],$pdf->convFont(''),'TB',1);          
        //} 
         
    }  
  }
  //dash of 應稅：銷售額 cells
  //change line style to dash line
  $linestyle1=array('dash'=>'3,3');
  $linestyle2=array('dash'=>'0');
  $pdf->SetLineStyle($linestyle);
  $step=array(1,2,4,5,7,8,10,11);
  $now_x_step=$left+$cw[0]+$cw[1]+$cw3[0];
  $now_y_step=$now_y;
  for($i=0;$i<10;$i++){
    if($i==3 || 6 ||9){
      $pdf->Line($now_x_step+$cw3[1]*$value,$now_y_step,$now_x_step+$cw3[1]*$value,$now_y_step+6*$rw[1],$linestyle1);
    }  
    else{
      $pdf->Line($now_x_step+$cw3[1]*$value,$now_y_step,$now_x_step+$cw3[1]*$value,$now_y_step+6*$rw[1],$linestyle2);    
    }
  }
/*
  foreach($step as $value){
    $pdf->Line($now_x_step+$cw3[1]*$value,$now_y_step,$now_x_step+$cw3[1]*$value,$now_y_step+6*$rw[1],$linestyle); 
  }
  */
  //change linestyle to default
  $linestyle=array('dash'=>'0');
  $pdf->SetLineStyle($linestyle); 
  
  $a=array(2,6,10,14,18,22);
  $now_x=$left+$cw[0]+$cw[1]+$cw2[1];
  $now_y=$top+$rw[0]*$line;
  $pdf->setY($now_y);
  foreach($a as $value){
    $pdf->setX($now_x);
    $pdf->cell($cw3[0],$rw[1],$pdf->convFont($value),'TBL',1,'L');
  }
  $now_x=$left+$cw[0]+$cw[1]+$cw2[1]+$cw3[0];
  $now_y=$top+$rw[0]*$line;
  $pdf->setXY($now_x,$now_y);
  $a=$cw2[1]-$cw3[0];
   $cww=array($cw3[0],$a);
  //稅額  (from databas)
  $now_x_step=$now_x+$cw3[0];
  for($j=0;$j<=5;$j++){
    for($i=0;$i<6;$i++)  
    {
      if($j==1 || $j==4 || $j==7){
          $pdf->setXY($now_x_step+$j*$cw3[1],$now_y+$i*$rw[1]);
          $pdf->cell($cww[1]/8,$rw[1],$pdf->convFont(''),'TBR',1);      
      }else{
          $pdf->setXY($now_x_step+$j*$cw3[1],$now_y+$i*$rw[1]);
          $pdf->cell($cww[1]/8,$rw[1],$pdf->convFont(''),'TB',1);
        }  
    }  
  }
  //稅額的虛線
  //change line style to dash line
  $linestyle=array('dash'=>'3,3');
  $pdf->SetLineStyle($linestyle);
  $step=array(0,1,3,4,6,7,9);
  foreach($step as $value){
    $pdf->Line($now_x+($cww[1]/8)*$value,$now_y,$now_x+($cww[1]/8)*$value,$now_y+6*$rw[1],$linestyle); 
  }
  //change linestyle to default
  $linestyle=array('dash'=>'0');
  $pdf->SetLineStyle($linestyle);  

  $cw4=array(15,61,54,38,23,5);
  $line=4;
  $a=array('稅','額','計','算');
  $now_x=$left+$cw[0]+$cw[1]+$cw2[0]+$cw2[3];
  $now_y=$top+$line*$rw[0];
  $pdf->setY($now_y);
  foreach($a as $key => $value){
    $pdf->setX($now_x);
    if($key==3)
      $pdf->cell($cw4[5],(9*$rw[1]+$rw[3])/4,$pdf->convFont($value),'LBR',1,'C');
    else
      $pdf->cell($cw4[5],(9*$rw[1]+$rw[3])/4,$pdf->convFont($value),'LR',1,'C');
  }
  $pdf->SetFont('my','B',6);
  $line=4;
  $header=array('代號','項目','稅額');
  $data1=array(1,7,8,10,11,12,13,14,15);
  $data2=array('本期(月)銷項稅額合計','得扣抵進項稅額合計','上期(月)累積留抵稅額','小計（7+8）','本期(月)應繳稅額(1-10)','本期(月)申報留抵稅額(10-1)','得退稅限額合計','','本期(月)累積留抵稅額(12-14)');

  $pdf->SetFont('my','B',6);
  $now_x=$left+$cw[0]+$cw[1]+$cw2[0]+$cw2[3]+$cw4[5];
  $now_y=$top+$line*$rw[0];
  $pdf->setXY($now_x,$now_y);
  //$cw4=array(15,60,55,35,30,17);
  //print header (代號、項目、稅額)
  foreach($header as $key=> $col)
  {
    $pdf->cell($cw4[$key],$rw[1],$pdf->convFont($col),1,0,'C');
  }
  $now_y=$now_y+$rw[1];
  $pdf->setXY($now_x,$now_y);
    //代號
    foreach($data1 as $key=> $row){
      if($key==7){
          $pdf->setX($now_x);
          $pdf->cell($cw4[0],$rw[3],$pdf->convFont($row),1,1,'C');
      }
      else{        
          $pdf->setX($now_x);
          $pdf->cell($cw4[0],$rw[1],$pdf->convFont($row),1,1,'C');
      }
    }
    $pdf->setY($now_y);
    //項目
    foreach($data2 as $key=> $row){
      if($key==7){ 
        $pdf->setX($now_x+$cw4[0]);
        $pdf->cell($cw4[3],$rw[3],$pdf->convFont($row),1,1,'L');
      }
      else{
        $pdf->setX($now_x+$cw4[0]);
        $pdf->cell($cw4[3],$rw[1],$pdf->convFont($row),1,1,'L');
      }
    } 
    $item=array("101","107","108","110","111","112","113","114","115");
    $pdf->setY($now_y);
     foreach($item as $key =>$value){
      if($key==7){      
        //$pdf->setXY($now_x+$cw4[0]+$cw4[3],$now_y+$key*$rw[1]*2);
        $pdf->setX($now_x+$cw4[0]+$cw4[3]);
        $pdf->cell($cw8[$key],$rw[3],$pdf->convFont($value),0,1,'L');
      }
      /*
      elseif($key==8){
       //$pdf->setXY($now_x+$cw4[0]+$cw4[3],$now_y+($key+1)*$rw[1]);
       $pdf->setX($now_x+$cw4[0]+$cw4[3]);
        $pdf->cell($cw8[$key],$rw[1],$pdf->convFont($value),0,1,'L');
      }   
      */   
      else{
        //$pdf->setXY($now_x+$cw4[0]+$cw4[3],$now_y+$key*$rw[1]);
        $pdf->setX($now_x+$cw4[0]+$cw4[3]);
        $pdf->cell($cw8[$key],$rw[1],$pdf->convFont($value),0,1,'L');
      }
    }
    //稅額header detail
    
    $pdf->SetFont('my','B',6);
    $pdf->setY($now_y+6.5*$rw[1]);
    $cw9=array(17,8,4,5,1); 
    //for 本期(月)應退稅額 stepping 
    $st=array($now_x+$cw4[0],$now_x+$cw4[0]+$cw9[0],$now_x+$cw4[0]+$cw9[0]+$cw9[1],$now_x+$cw4[0]+$cw9[0]+$cw9[1]+$cw9[2],$now_x+$cw4[0]+$cw9[0]+$cw9[1]+$cw9[2]+$cw9[3]);
    $data3=array('本期(月)應退稅額(',"12>13\n13>12",'則為',"13\n12",')');
    foreach($data3 as $key =>$value){
      if($key==1 || $key==3){
        $pdf->setXY($st[$key],$now_y+$rw[1]*7.25);
        $pdf->multicell($cw9[$key],3,$pdf->convFont($value),0,0);
      }else{
        $pdf->setXY($st[$key],$now_y+$rw[1]*7);
        $pdf->cell($cw9[$key],$rw[3],$pdf->convFont($value),0,0);
      }
    }
    //$pdf->SetFont('my','B',6);  
    $pdf->setY($now_y);
    //稅額計算：項目
    for($i=1;$i<=9;$i++){ 
      if($i==8)
      {
        $pdf->setX($now_x+$cw4[0]+$cw4[3]);
        $pdf->cell($cw4[4],$rw[3],'',1,1,'L');        
      }
      else{
        $pdf->setX($now_x+$cw4[0]+$cw4[3]);
        $pdf->cell($cw4[4],$rw[1],'',1,1,'L');
      }
    }
    //稅額
    for($i=0;$i<9;$i++){
      $pdf->setY($now_y);
      for($j=0;$j<9;$j++){ 
        if($j==7){
          $pdf->setX(($now_x+$cw4[0]+$cw4[3]+$cw4[4])+$i*($cw4[2]/9));
          $pdf->cell($cw4[2]/9,$rw[3],'',1,1,'L');        
        }
        else{
          $pdf->setX(($now_x+$cw4[0]+$cw4[3]+$cw4[4])+$i*($cw4[2]/9));
          $pdf->cell($cw4[2]/9,$rw[1],'',1,1,'L');  
        }
      }  
    }   
 
  $right_stop=$left+$cw[0]+$cw[1]+$cw2[0]+$cw2[3];
  $pdf->SetFont('my','B',8);  
  $line=9.25;
  $now_x=$left+$cw[0]+$cw[1]+$cw2[0]+$cw2[3];
  $now_y=$top+$cell_height*$line;
  $pdf->setXY($now_x,$now_y);
  $cell_size=$cw4[0]+$cw4[3]+$cw4[5];
  $a=array('','本期（月）應退稅額處理方式','');
    foreach($a as $key=> $value){ 
      $pdf->setX($now_x);
      $pdf->cell($cell_size,$rw[1],$pdf->convFont($value),'LR',1,'C');
    }

  $now_x=$left+$cw[0]+$cw[1]+$cw2[0]+$cw2[3]+$cw4[0]+$cw4[3]+$cw4[5];
  $cell_size=$size_x-$right-$now_x;
  $a=array('□ 利 用 存 款 帳 戶 劃 撥','□ 領 取 退 稅 支 票','');
    $pdf->setY($now_y);
    foreach($a as $key=> $value){ 
        $pdf->setX($now_x);
        $pdf->cell($cell_size,$rw[1],$pdf->convFont($value),1,1,'L');
    }
    
  $now_x=$left+$cw[0]+$cw[1]+$cw2[0]+$cw2[3];  
  $pdf->setX($now_x);
  $cell_size=$cw4[0]+$cw4[1];
  $pdf->multicell($cell_size,$rw[1],$pdf->convFont("免稅出口區內外銷事業、科學工業園區內園區事業及海關管理之保稅工廠、保稅倉庫或物流中心按進口報關程序銷售貨物至我國境內其他地區之免開立統一發票銷售額"),1,'L');
  
  //Right-Bottom
  $line=12.25;
  $cw5=array(67,68,37,10,10,10,38,10,10,10); 
  $now_x=$left+$cw[0]+$cw[1]+$cw2[0]+$cw2[3];  

  $now_y=$top+$line*$rw[0];
  $pdf->setY($now_y);
  $pdf->setX($now_x);
  $pdf->cell($cw5[0],$rw[1],$pdf->convFont('申報單位蓋章處(統一發票專用章)'),1,0,'C');       
  $pdf->setX($now_x+$cw5[0]);                                        
  $pdf->cell($cw5[1],$rw[1],$pdf->convFont('核收機關及人員蓋章處'),1,1,'C');
  $pdf->setX($now_x);
  $a=array('附　1.統一發票明細表　　      份','2.進項憑證　    　 冊　  　 　份', '3.海關代徵營業稅繳納證        份','4.退回(出)及折讓證明單　　　  份', '5.營業稅繳款書申報聯　　　    份' ,'6.零稅率銷售額清單            份');
  foreach($a as $key => $value){
    $pdf->setX($now_x);
    $pdf->cell($cw5[0],$rw[1],$pdf->convFont($value),'RL',1);    
  }
  $pdf->setX($now_x);
  $pdf->cell($cw5[0],4*$rw[1],'','LR',1,'C');
  $line=17.25;
  $a=array('申請日期：','年','月','日');
  foreach($a as $key=>$value){
    if($key==0){
      $pdf->setX($now_x);
      $pdf->cell($cw5[2],$rw[1],$pdf->convFont($value),'L',0,'L');
    }
    else{
      $pdf->setX($now_x+$cw5[2]+($key-1)*$cw5[3]);
      $pdf->cell($cw5[2+$key],$rw[1],$pdf->convFont($value),0,0,'L');      
    }     
  }
  $line=12.25;
  $now_x=$right_stop+$cw5[0];
  //line with left margin (table)
  $now_y=$line*$rw[0];
  $pdf->setXY($now_x,$now_y);
  $pdf->cell($cw5[1],15.25*$rw[1],'','R',1,'C');

  //$now_y=$top+$line*$rw[0];
  //$pdf->setY($now_y);
  $line=19.75;
  
  $now_y=$line*$rw[0];
  $pdf->setY($now_y);
  $b=array('核收日期：','年','月','日');
  foreach($b as $key=>$value){
    if($key==0){
      $pdf->setXY($now_x,$now_y);
      $pdf->cell($cw5[6],$rw[1],$pdf->convFont($value),'L',0,'L');
    }
    elseif($key==3){
      $pdf->setXY($now_x+$cw5[6]+($key-1)*$cw5[7],$now_y);
      $pdf->cell($cw5[6+$key],$rw[1],$pdf->convFont($value),'R',0,'L'); 
    }
    else{
      $pdf->setXY($now_x+$cw5[6]+($key-1)*$cw5[7],$now_y);
      $pdf->cell($cw5[6+$key],$rw[1],$pdf->convFont($value),0,0,'L');      
    }     
  }  
             
  $line=5;
  $ticket=array('三聯式發票、電子計算機發票','收銀機發票（三聯式）','二聯式發票、收銀機發票（二聯式）','免用發票','減：退回及折讓','合計','銷售額總計');
  $pdf->setXY($left+$cw[0],$top+$rw[0]*$line);
  foreach($ticket as $key => $value){
    $pdf->setX($left+$cw[0]);
    if($key==6){
      $pdf->cell($cw[1],$rw[2],$pdf->convFont($value),1,1,'C');      
    }
    else{
      $pdf->cell($cw[1],$rw[1],$pdf->convFont($value),1,1,'C');
    }         
  }
  
  $line=9.25;
  $cell_size=$cw[1]+$cw[2];
  $pdf->setXY($left+$cw[0],$top+$rw[0]*$line);
  $pdf->cell($cell_size,$rw[0],$pdf->convFont('區 分'),'LRT',1,'R');
  //Diagonal
  $pdf->line($left+$cw[0],$top+$rw[0]*$line,$left+$cw[0]+$cell_size,$top+$cell_height*$line+$cell_height);
  $pdf->setXY($left+$cw[0],$top+$rw[0]*$line);
  $pdf->cell($cell_size,$rw[0],$pdf->convFont('項 目'),'LR',1,'L');
  $now_x=$left+$cw[0]+$cw[1]+$cw[2];
  $cw6=array(100,50,50);
  $pdf->setXY($left+$cw[0]+$cw[1]+$cw[2],$top+$cell_height*$line);
  $pdf->cell($cw6[0],$rw[1],$pdf->convFont('得  扣  抵  進  項  稅  額'),1,1,'C');
  $pdf->setXY($left+$cw[0]+$cw[1]+$cw[2],$top+$cell_height*$line+$cell_height/2);
  $pdf->cell($cw6[1],$rw[1],$pdf->convFont('銷　　售　　額'),1,0,'C');
  $pdf->cell($cw6[2],$rw[1],$pdf->convFont('稅　　　　   額'),1,0,'C');

  
  $line=10.25;
  $pdf->SetFont('my','B',8);
  $ticket=array("統一發票扣抵聯\n(包括電子計算機發票)",'三聯式收銀機發票扣抵聯',"載有稅額其他憑證\n(包括二聯式收銀機發票)",'海關代徵營業稅繳納證扣抵聯','減：退出及折讓','合　　　計',"進項總金額\n(包括不得扣抵憑證及普通收據)");
  $pdf->setXY($left+$cw[0],$top+$rw[0]*$line);
  $h=$cell_height;
  foreach($ticket as $key => $value){
    $pdf->setX($left+$cw[0]);
    if($key==0 || $key==2 || $key==6)
      $pdf->multicell($cw[1],$rw[1],$pdf->convFont($value),1,'C');
    else{
     $pdf->cell($cw[1],$rw[0],$pdf->convFont($value),1,1,'C');
    }
  }
  $outside=array('進口貨物','購買國外勞務');
  $now_x=$left;
  $pdf->setX($now_x);
  foreach($outside as $key => $value){
        $pdf->setX($left);
        $pdf->cell($cw[0]+$cw[1],$rw[1],$pdf->convFont($value),1,1,'C');
  }
  $line=17.25; 
  $now_x=$left+$cw[0]+$cw[1]; 
  $now_y=$pdf->GetY();  
  $pdf->setXY($now_x,$now_y);
  $cell_size=$cw[2]+$cw2[0];
  $a=array('73','元','74','元');
  $now_y=$pdf->getY()-$rw[0];
  $pdf->setY($now_y);
  foreach($a as $key => $value){
    if($key==0 || $key==2){
      $pdf->setX($now_x);
      $pdf->cell($cw[1],$rw[1],$pdf->convFont($value),'LT',0,'L');
    }
    if($key==1 || $key==3){
        $pdf->setX($now_x+$cw[2]);
        $pdf->cell($cw6[0],$rw[1],$pdf->convFont($value),'T',1,'R');
    }
  } 
  
  $line=23.25;   
  $now_y=$pdf->getY();  
  $pdf->setXY($left,$now_y);   
  $a=array('說','明');
  foreach($a as $key=> $value){
    $pdf->setX($left);
    if($key ==0)
      $pdf->cell($cw[0],$rw[0],$pdf->convFont($value),'LR',1,'C');
    else      
      $pdf->cell($cw[0],$rw[0],$pdf->convFont($value),'LRB',0,'C');
  }    
   $now_x=$left+$cw[0]+$cw[1];
   
  $pdf->SetFont('my','B',8);
  $cell_size=$size_x-$left-$right-$cw[0];
  $now_x=$left+$cw[0];
  $line=18.25;
  $now_y=$top+$line*$rw[0];
  $pdf->setXY($now_x,$now_y);
  $a=array('一、本申報書適用專營應稅及零稅率之營業人填報。','二、如營業人申報當期（月）之銷售額包括有免稅、特種稅額計算銷售額者，請改用（403）申報書申報。');
  foreach($a as $key=> $value){
    $pdf->setX($left+$cw[0]);
    if($key ==0)
      $pdf->cell($cell_size,$rw[0],$pdf->convFont($value),'TR',1,'L');
    else      
      $pdf->cell($cell_size,$rw[0],$pdf->convFont($value),'BR',1,'L');
  }
  $line=10.25;                                        
  $pdf->SetFont('my','B',6);
  $ticket=array('進貨及費用','固定資產','進貨及費用','固定資產','進貨及費用','固定資產','進貨及費用','固定資產','進貨及費用','固定資產','進貨及費用','固定資產','進貨及費用','固定資產');

  $now_x=$left+$cw[0]+$cw[1];
  $pdf->setY($top+$rw[0]*$line);
  foreach($ticket as $key => $value){
        $pdf->setX($now_x);
        $pdf->cell($cw[2],$rw[1],$pdf->convFont($value),1,1,'C');         
  }
  //***************************************************
  $cw8=array(6,4,8,3.8);
  $a=array(28,30,32,34,36,38,78,80,40,42,44,46);
  $now_x=$left+$cw[0]+$cw[1]+$cw[2];
  $now_y=$top+$cell_height*$line;
  $pdf->setY($now_y);
  foreach($a as $key=>$value){
    $pdf->setX($now_x);
    if($key==12 || $key==13){
      $pdf->cell($cw8[0],$rw[1],$pdf->convFont($value),0,1,'L');   
    }else{
      $pdf->cell($cw8[0],$rw[1],$pdf->convFont($value),'TBL',1,'L');
    }      
  }
  $a=array('48','元','49','元');
  $now_y=$pdf->getY();
  $pdf->setY($now_y);
  foreach($a as $key => $value){
    //48.49
    if($key==0 || $key==2){
      $pdf->setX($now_x);
      $pdf->cell($cw6[1],$rw[1],$pdf->convFont($value),'LBT',0,'L');
    }
    //元
    if($key==1 || $key==3){
        $pdf->setX($now_x+$cw6[1]);
        $pdf->cell($cw6[2],$rw[1],$pdf->convFont($value),'T',1,'R');
    }
  } 
  $now_x=$left+$cw[0]+$cw[1]+$cw[2]+$cw8[0];
  $now_y=$top+$rw[0]*$line;
  $pdf->setXY($now_x,$now_y);
  //得抵扣進項稅額 : 銷售額 from database
  for($j=0;$j<=11;$j++){
    for($i=0;$i<12;$i++)  
    {
    /*
      if($i==2 || $i==5 || $i==8){
          $pdf->setXY($now_x+$j*$cw8[1],$now_y+$i*$rw[1]);
          $pdf->cell($cw8[1],$rw[1],$pdf->convFont(''),'LBR',1);
      }
      else{
      */
          $pdf->setXY($now_x+$j*$cw8[1],$now_y+$i*$rw[1]);
          $pdf->cell($cw8[1],$rw[1],$pdf->convFont(''),'LB',1);     
      //}  
    }  
  }
  
  $b=array(29,31,33,35,37,39,79,81,41,43,45,47);
  $now_x=$left+$cw[0]+$cw[1]+$cw[2]+$cw6[1];
  $now_y=$top+$cell_height*$line;
  $pdf->setY($now_y);
  
  foreach($b as $value){
    $pdf->setX($now_x);
    $pdf->cell($cw8[2],$rw[1],$pdf->convFont($value),'TBL',1,'L');      
  }
  $now_x=$left+$cw[0]+$cw[1]+$cw[2]+$cw6[1]+$cw8[2];
  $now_y=$top+$rw[0]*$line;
  $pdf->setXY($now_x,$now_y);
  //得抵扣進項稅額：稅額  from database
  for($j=0;$j<=10;$j++){
    for($i=0;$i<12;$i++)  
    {
      if($j==2 || $j==5 || $j==8){
          $pdf->setXY($now_x+$j*$cw8[3],$now_y+$i*$rw[1]);
          $pdf->cell($cw8[3],$rw[1],$pdf->convFont(''),'LBR',1);
      }
      else{
         $pdf->setXY($now_x+$j*$cw8[3],$now_y+$i*$rw[1]);
          $pdf->cell($cw8[3],$rw[1],$pdf->convFont(''),'LBR',1);
      }  
    }  
  }
  //************************************************
  $pdf->SetFont('my','B',8);
  
	//center-top
	$pdf->SetXY($center,$top);

	$pdf->SetXY($center-60,$top+10);	
  $pdf->MultiCell(110,5,$pdf->convFont(buildCenterInfo($pdf)),0,'C');
  $bottom=$pdf->GetY(); // detail data from here

  //draw right-top
  $cw3=array(7,40,20,14);
  $pdf->SetFont('my','B',10);
  $right_margin=15;
  $pdf->setXY(325,10);
  $pdf->Cell(20,10,$pdf->convFont('表單編號：8-1-1'),0,0,'C');
  $pdf->SetFont('my','B',8);
  $pdf->setXY(330,$top);
  $pdf->multicell($cw[0],3,$pdf->convFont("第\n一\n聯\n：\n申\n報\n聯\n　\n營\n業\n人\n持\n向\n稽\n徵\n機\n關\n申\n報"),0,'C');
  $pdf->setXY(340,$top);
  $pdf->multicell($cw[0],3,$pdf->convFont("第\n二\n聯\n：\n收\n執\n聯\n　\n營\n業\n人\n於\n申\n報\n時\n併\n同\n申\n報\n聯\n交\n由\n稽\n徵\n機\n關\n核\n章\n後\n作\n為\n申\n報\n憑\n證\n。"),0,'C');
	$pdf->SetXY($size_x-$right-67,$top);
	$cell_size=7;
	$ps=array('註','記','欄');
  foreach($ps as $key => $value){
    $pdf->setX($size_x-$right-67);
    if($key==0)
      $pdf->cell($cw3[0],$rw[0],$pdf->convFont($value),'LRT',1,'C');

    if($key==1)
      $pdf->cell($cw3[0],$rw[0],$pdf->convFont($value),'LR',1,'C');      
      
    if($key==2)
      $pdf->cell($cw3[0],$rw[0],$pdf->convFont($value),'LR',1,'C');
  }	
	$cell_size=40;
	$pdf->SetXY($size_x-$right-60,$top);
  $top_right=array('核准按月申報','總機構彙總申報','各單位分別申報');
  $h=$cell_height;
  foreach($top_right as $key => $value){
    if($key==0){
      $pdf->setX($size_x-$right-$cw3[2]-$cw3[1]);
      $pdf->cell($cw3[1],$rw[0],$pdf->convFont($value),1,1,'C');
      }
    else{
       $pdf->setX($size_x-$right-$cw3[2]-$cw3[1]+$cw3[3]);
      $pdf->cell($cw3[1]-$cw3[3],$rw[0],$pdf->convFont($value),'LBR',1,'C');
    }
  }
  //3 blank cell vertically
  $now_x=$size_x-$right-$cw3[2];
  $now_y=$top;
  $pdf->setY($now_y);
  for($i=1;$i<=3;$i++){
    $pdf->setX($now_x);
    $pdf->cell($cw3[2],$rw[0],'',1,1,'C');
  }
  $now_x=$size_x-$right-60;
  $now_y=$top+$rw[0];
  $a=array('核  總','准  繳','合  單','併  位');
  $pdf->setXY($now_x,$now_y);
  foreach($a as $key => $value){
    $pdf->cell($cw3[3],$rw[1],$pdf->convFont($value),0,1,'C');
    $pdf->setX($now_x);
  }  
}




$pdf=new myPDF('L','mm',array(250,350));
$pdf->SetLineWidth(0.2);
//$pdf->TERMS=$PDF_TERMS; // 多語名詞定義
$pdf->firmInfo=$info;
$pdf->data=$quotation; // 報價單內容
$pdf->AliasNbPages();
$pdf->SetTitle('營業人銷售額與稅額申報書（401）');
//$pdf->SetAuthor('Cyberhood');
$pdf->AddMyFont();

$pdf->Open(); 
$pdf->AddPage('L');
$center=149;

$cell_size_x=30;
$cell_size_y=10;
$margin_left=20;
$margin_top=20;
$top=15;
$left=15;

//Title
$title=$pdf->convFont('營業人銷售額與稅額申報書（401）');
$pdf->SetMyFont('my','BU',16); // family,style,size
$pdf->setXY($center,$top);
$pdf->Cell($cell_size_x,$cell_size_y,$title,0,1,'C');

Master($pdf);

$pdf->replaceTotalPageNum();
if($dl)
	$pdf->Output('營業人銷售額與稅額申報書（401）.pdf','D');
else
	$pdf->Output('營業人銷售額與稅額申報書（401）.pdf','D');	
?>