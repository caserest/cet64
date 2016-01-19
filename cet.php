<?php

//微博协会四六级成绩查询代码，掌上大学接口。
//输入cet后可看到结果
//另外的文件为46ji.php
define("TOKEN", "weiboxiehui");
$wechatObj = new wechatCallbackapiTest();
if (!isset($_GET['echostr'])) {
    $wechatObj->responseMsg();
}else{
    $wechatObj->valid();
}

class wechatCallbackapiTest
{
    //验证签名
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if($tmpStr == $signature){
            echo $echoStr;
            exit;
        }
    }

    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            $this->logger("R ".$postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);

            $result = "";
            switch ($RX_TYPE)
            {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text":
                    $result = $this->receiveText($postObj);
                    break;
            }
            $this->logger("T ".$result);
            echo $result;
               $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $mediaid=$postObj->MediaId;
            $megid=$postObj->MsgId;
            $MsgType=$postObj->MsgType;
            $keyword = trim($postObj->Content);
            $j=$postObj->Location_Y;
            $w=$postObj->Location_X;
            $label=$postObj->Label;
            $keyword = trim($postObj->Content);
            $keystr=strtoupper($keyword);
            $time = time();
            $mem=memcache_init();
            //$ev = $postObj->Event;
            $textTpl = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                            <FuncFlag>0</FuncFlag>
                            </xml>"; 
          
            $newsTpl = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[news]]></MsgType>
                            <ArticleCount>1</ArticleCount>
                            <Articles>
                            <item>
                            <Title><![CDATA[%s]]></Title> 
                            <Description><![CDATA[%s]]></Description>
                            <Url><![CDATA[%s]]></Url> 
                            </item>
                            </Articles>
                            <FuncFlag>1</FuncFlag>
                            </xml>";   
            

            if(!empty( $keyword )){
                if ($keystr=="CET") {
                    $msgType = "text";
                   



                    
                    
                    
                    
                    $conn = @mysql_connect(SAE_MYSQL_HOST_M .':'. SAE_MYSQL_PORT, SAE_MYSQL_USER, SAE_MYSQL_PASS) or die('数据库连接失败，错误信息：'.mysql_error());
    
                            //第二步，选择指定的数据库，设置字符集
                            mysql_select_db(SAE_MYSQL_DB) or die('数据库错误，错误信息：'.mysql_error());
                             mysql_query('SET NAMES UTF8') or die('字符集设置错误'.mysql_error());
                            $openid=$fromUsername;
                            $sql = "SELECT * FROM cet46 WHERE openid ='{$openid}'";
                            $resultsql = mysql_query($sql);
                            $row = mysql_fetch_array($resultsql);  
                            if(empty($row)){//检测绑定
                            $content="你未绑定准考证和姓名，请先回复四/六级+准考证号+姓名进行绑定.如\n【四级666666+TFboys】(+号不能省略)\n\n【忘记准考证】\n如有疑问，请加小编工作微信：";
                            // $resultStr = transmitText($postObj,$content);
                            // echo $resultStr;//反馈信息
                            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $content);
                            echo $resultStr;
                            }
                            else
                            {
                                
                                $name=$row['name'];
                                $number=$row['zhunkaozheng'];
                            $name_gb2312 = urlencode(mb_convert_encoding($name, 'gb2312', 'utf-8'));//转码
                            $data = "id=".$number."&name=".$name_gb2312;
                            $url = "http://cet.99sushe.com/find";//成绩接口
                            $headers = array(//头文件
                            "User-Agent: Mozilla/5.0 (Windows NT 5.1; rv:14.0) Gecko/20100101 Firefox/14.0.1",
                            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
                            "Accept-Language: en-us,en;q=0.5",
                            "Referer: http://cet.99sushe.com/"
                            );
                            $curl = curl_init();
                            curl_setopt($curl, CURLOPT_URL, $url);
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($curl, CURLOPT_POST, 1);
                            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                            $output = curl_exec($curl);
                            curl_close($curl);//curl提交post请求
                            $result = iconv("GBK", "UTF-8//IGNORE", $output);//转码
                            $score = explode(",",$result);

                            $name= $score[6];
                            $zongfen=$score[4];
                            $xx=$score[5];
                            $tingli=$score[1];
                            $yuedu=$score[2];
                            $xiezuo=$score[3];//各项成绩
                            $zhunkaozheng=$row['zhunkaozheng'];


                            $kslx=substr($row['zhunkaozheng'],9,1);//截取准考证，判断考试类型
                            if($kslx==1){
                            $kslb="四级";
                            }elseif($kslx==2){
                            $kslb="六级";  
                            }
                            
                            $sql = "SELECT COUNT(DISTINCT name) FROM `cet46` WHERE score>{$zongfen} and kslx={$kslx}";  
                            $resultsql = mysql_query($sql);
                            $place=mysql_result($resultsql,0)+1;
                             //$contentStr=$place;


                             //获取用户当前排名 kslx是判断四六级
                          
                            $sql = "UPDATE cet46 SET score = '{$zongfen}',place='{$place}',kslx = '{$kslx}' WHERE openid = '{$openid}'";
                            mysql_query($sql);
                            //更新用户信息到数据库（方便维护）
                            $sql = "SELECT count(distinct zhunkaozheng) FROM `cet46` WHERE score>=1 and  kslx={$kslx}";
                            $resultsql = mysql_query($sql);
                            $people=mysql_result($resultsql,0);
                            //获取成功查询人数 kslx是判断四六级
                            
                            $news=array(
                            'title'=>"四六级成绩查询",
                            'description'=>"姓名：{$name}\n学校：{$xx}\n考试类别：{$kslb}\n准考证号：{$zhunkaozheng}\n考试时间：2015年6月\n————————\n你的成绩总分：{$zongfen}\n听力：{$tingli}\n阅读：{$yuedu}\n写作和翻译：{$xiezuo}\n".(($zongfen>=425)?"\n恭喜你通过考试！":"\n革命尚未成功，同志仍须努力！")."\n目前你在本校同类考试的名次是第{$place}名\n{$kslb}共{$people}人(实时更新，再次回复【cet】刷新排名)\n\nPowered by caserest"
                                );
                            $imagetextTpl="<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[news]]></MsgType>
                            <ArticleCount>1</ArticleCount>
                            <Articles>
                            <item>
                            <Title><![CDATA[%s]]></Title>
                            <Description><![CDATA[%s]]></Description>
                            <PicUrl><![CDATA[]]></PicUrl>
                            <Url><![CDATA[%s]]></Url>
                            </item>
                            </Articles>
                            </xml> ";
                            $nurl="";
                            $result= sprintf($imagetextTpl, $fromUsername, $toUsername, time(), $news['title'],$news['description'],$nurl);
                            echo $result;//图文输出



                           

                            }  
                            mysql_close($conn);
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                  

                     
                   


                  
                    
                }



           }else{
                    echo "Input something...";
                }
            
}else {
            echo "";
            exit;
            }
  
}

      
    

    private function receiveText($object)
    {
        $keyword = trim($object->Content);
        $url = "http://apix.sinaapp.com/weather/?appkey=".$object->ToUserName."&city=".urlencode($keyword); 
        $output = file_get_contents($url);
        $content = json_decode($output, true);

        $result = $this->transmitNews($object, $content);
        return $result;
    }

    private function transmitText($object, $content)
    {
        if (!isset($content) || empty($content)){
            return "";
        }
        $textTpl = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[text]]></MsgType>
        <Content><![CDATA[%s]]></Content>
        </xml>";
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }

    private function transmitNews($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return "";
        }
        $itemTpl = "    <item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
        <Url><![CDATA[%s]]></Url>
             </item>
        ";
        $item_str = "";
        foreach ($newsArray as $item){
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $newsTpl = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[news]]></MsgType>
        <Content><![CDATA[]]></Content>
        <ArticleCount>%s</ArticleCount>
        <Articles>
        $item_str</Articles>
        </xml>";

        $result = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }

    private function logger($log_content)
    {
      
    }

   
    


     


   
    
}
?>