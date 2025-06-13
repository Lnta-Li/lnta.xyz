//获得文档字段管理器的字段信息(字段id、文档id、字段名称、字段类型、值)
function ch_field($fid, $aid=0)
{
    global $dsql, $fields;
    if( !isset($fields[$fid]) )
    {
        $row = $dsql->GetOne("SELECT id,title,dtype FROM `#@__diyforms` WHERE id='$fid'");
        $fields[$fid] = $row;
    }
    if($aid==0) return $fields[$fid];
    else
    {
        if( !isset($fields[$fid][$aid]) )
        {
            $row = $dsql->GetOne("SELECT fieldname,value FROM  `#@__diyform_content` WHERE id='$aid' AND diyid='{$fid}'");
            $fields[$fid][$aid] = $row;
        }
        return $fields[$fid][$aid];
    }
}

/*------------------------
//获取特定字段的值
function field(字段名称)
----------------------------*/
function lib_field(&$ctag, &$refObj)
{
    global $dsql,$sqlCtag;
    $attlist="name|default|autofield";
    FillAttsDefault($ctag->CAttribute->Items,$attlist);
    extract($ctag->CAttribute->Items, EXTR_SKIP);

    if(!isset($name))
    {
        $name = "";
    }

    if(!isset($default))
    {
        $default = "";
    }

    if(!isset($autofield))
    {
        $autofield = 0;
    }

    $revalue = '';
    
    if(isset($refObj->Fields[$name]))
    {
        if($name == 'id' || $name == 'aid'||$name == 'typeid'||$name == 'channelid'||$name == 'click'||$name=='arcrank'
           ||$name=='senddate'||$name == 'money'||$name=='filename'||$name=='voteid'||$name=='lastpost'
           ||$name=='scores'||$name=='goodpost'||$name=='badpost'||$name=='notpost'||$name=='hide_thumb')
        {
            $revalue = $refObj->Fields[$name];
        }
        else if($name == 'small_img')
        {
            $revalue = $refObj->Fields[$name];
        }
        else if($name == 'ismake')
        {
            $revalue = $refObj->Fields[$name];
            $revalue = ( $revalue == -1 ) ? 0 : 1;
        }
        else if(preg_match('#[_a-z0-9]+#i',$name))
        {
            if(!isset($refObj->Fields[$name]))
            {
                $revalue = '';
            }
            else
            {
                $revalue = $refObj->Fields[$name];
            }
        }
        else
        {
            $revalue = $default;
        }
    }
    else
    {
        $revalue = $default;
    }
    
    return $revalue;
} 