<?php


define("TOKEN", "weiboxiehui");
require_once 'medoo.php';
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
           $keystr=mb_substr($keyword,0,2,'utf-8');
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
            $musicTpl = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[music]]></MsgType>
                            <Music>
                            <Title><![CDATA[秋风词]]></Title>
                            <Description><![CDATA[湖南省博物馆]]></Description>
                            <MusicUrl><![CDATA[http://www.hnmuseum.com/hnmuseum/service/download/qfc.mp3]]></MusicUrl>
                            <HQMusicUrl><![CDATA[http://www.hnmuseum.com/hnmuseum/service/download/qfc.mp3]]></HQMusicUrl>
                           
                            </Music>
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
               
                  if ($keystr=='四级'||$keystr=='六级') 
            {
                    
                    $msgType = "text";

                    $keyword=mb_substr($keyword, 2,strlen($keyword),'utf-8');
                    $keyword = str_replace("＋","+",$keyword);//容错 全角替换
                    $keyword = str_replace("。","",$keyword);//容错
                    $keyword=trim($keyword);
                    $cetarray=explode("+",$keyword);
                    $zhunkaozheng=trim($cetarray[0]);
                    $name=trim($cetarray[1]);
                    if(preg_match('/^\d{15}$/',$zhunkaozheng)){ //15位数字考号+姓名
                     
                      if(empty($name)){
                        $content="你输入的格式有误，请重新输入\n格式：四/六级考号+姓名\n('+'号不能省略)\n例如：四级123456+TFboys";//提示
                        // echo $resultStr;
                      }else{
                      // echo $zhunkaozheng;
                      /*数据库插入用户信息*/
                      /*数据库链接*/
                      $link=mysql_connect(SAE_MYSQL_HOST_M.':'.SAE_MYSQL_PORT,SAE_MYSQL_USER,SAE_MYSQL_PASS);
                      if($link)
                      {
                        mysql_select_db(SAE_MYSQL_DB,$link);
                        $mysql = new SaeMysql();
                      }
                      $openid=$fromUsername;
                      /*数据库判定是否为空*/
                      $sql = "SELECT * FROM cet46 WHERE openid ='{$openid}'";
                        $resultsql = mysql_query($sql);
                        $row = mysql_fetch_array($resultsql);  
                      if(empty($row)){
                        $sql = "INSERT INTO `cet46`(`openid`,`zhunkaozheng`,`name`) VALUES ('{$openid}','{$zhunkaozheng}','{$name}')";//插入信息
                        mysql_query($sql);
                        $content="您已经成功绑定\n\n准考证\n【{$zhunkaozheng}】\n\n姓名\n【{$name}】\n\n如有误请重新按格式输入\n\n 回复【cet】即可立马查成绩和排名";//提示
                      }else{
                        if(preg_match("/[0-9]/",$row['score'])){//检测是否成功查询过
                        $content="您已经成功查询过成绩。不能更换准考证哦。";//提示
                        }else{
                        $sql = "UPDATE cet46 SET zhunkaozheng = '{$zhunkaozheng}',name = '{$name}' WHERE openid = '{$openid}'";//更新信息
                        mysql_query($sql);
                        $content="你已成功更新绑定\n\n准考证\n【{$zhunkaozheng}】\n\n姓名\n【{$name}】\n\n若有误请重新按格式输入。\n\n 回复【cet】立马查成绩和排名";//提示
                      
                        
                        }
                        }  
                      
                    }
                      
                      //$resultStr = transmitText($postObj,$content);
                    //echo $resultStr;
                      $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $content);
                        echo $resultStr;
                    }else{
                      /*若准考证+姓名格式有误*/
                      $content="你输入的准考证号(15位)有误，请重新输入\n格式：四/六级考号+姓名\n('+'号不能省略)\n例如：四级123456+TFboys";//提示
                    //$resultStr = transmitText($postObj,$content);
                    //echo $resultStr;
                        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $content);
                        echo $resultStr;
                    }





                    //$msgType = "text";
                       // $locations=mb_substr($keyword, 2,strlen($keyword),'utf-8'); 
                       // $url="http://1.caserest.sinaapp.com/weather_location.php?locations=$locations";
                        //$fa=file_get_contents($url);//得到网站内容
                        //$contentstr=$fa;
                        //$contentStr = $location;
                        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $content);
                        echo $resultStr;
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