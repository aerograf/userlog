<{$logo}>
<div class="odd border x-small">
    <div class="floatleft left">
        <{if $logs}>
            <{$status}> <{$pages}> <{$smarty.const._AM_USERLOG_LOG_PAGE}>
        <{else}>
            <{$smarty.const._AM_USERLOG_LOG_ERROR}>
        <{/if}>
        <{if $options}>
            <span class="xo-buttons">
            <a class="ui-corner-all tooltip" title="<{$smarty.const._RESET}>"
               href="logs.php?op=<{if $query_entry}><{$query_entry}><{/if}>">
                <img src="<{xoModuleIcons16 on.png}>" alt="<{$smarty.const._RESET}>"
                     title="<{$smarty.const._RESET}>"><{$smarty.const._RESET}>
            </a>
        </span>
        <{/if}>
        <div class="cursorpointer bold"
             onclick="ToggleBlock('formhead',(this.firstElementChild || this.children[0]) , '<{xoModuleIcons16 green.gif}>', '<{xoModuleIcons16 green_off.gif}>','<{$smarty.const._AM_USERLOG_HIDE_FORM}>','<{$smarty.const._AM_USERLOG_SHOW_FORM}>','toggle_block','toggle_none')">
            <img id="<{$formHeadToggle.icon}>"
                 src="<{if $formHeadToggle.icon == 'green'}><{xoModuleIcons16 green.gif}><{else}><{xoModuleIcons16 green_off.gif}><{/if}>"
                 alt="<{$formHeadToggle.alt}>"
                 title="<{$formHeadToggle.alt}>"><span id="formheadtext"><{$formHeadToggle.alt}></span>
        </div>
    </div>
    <div class="floatright left"><{$formNav}></div>
    <div class="clear"></div>
</div>
<div class="even border x-small">
    <div id="formhead" class="<{$formHeadToggle.toggle}>">
        <{$formHead}>
        <div class="clear"></div>
    </div>
    <div class="clear"></div>
</div>
<{if $options}>
    <div class="even border x-small">
        <{foreach item=val key=op from=$options}>
            <{assign var=header value=$op|replace:'GT':''|replace:'LT':''}>
            <{assign var=tt value=$op|replace:$header:''|replace:'GT':'>'|replace:'LT':'<'}>
            <{if $headers.$header}><{$headers.$header}><{$tt}><{else}><{$headers.request_method}>[<{$op}>]<{/if}>=
            <b><{$val}></b>
            <br>
        <{/foreach}>
    </div>
<{/if}>
<{if $logs}>
    <{assign var=widthC value=5}>
    <div class="outer">
        <{if $pages gt 1}>
            <form name="bulk" action="logs.php?op=<{if $query_page}>&amp;<{$query_page}><{/if}>
                <{if $query_entry}><{$query_entry}><{/if}>" method="POST" onsubmit="if(window.document.bulk.op.value =='') {return false;}
                  else if (window.document.bulk.op.value =='del') {return deleteSubmitValid();}
                  else {window.document.bulk.limitentry.value = 0};">
                <{securityToken}><{*//mb*}>
                <input type="hidden" name="confirm" value="1">
                <input type="hidden" name="log_id" value="bulk">
                <input type="hidden" name="limitentry" value="<{$limitentry}>">
                <div class="floatright xo-buttons">
                    <select name="op">
                        <option value=""><{$smarty.const._AM_USERLOG_LOG_SELECT_BULK}></option>
                        <option value="del"><{$smarty.const._AM_USERLOG_LOG_PURGE_ALL}></option>
                        <option value="export-csv"><{$smarty.const._AM_USERLOG_LOG_EXPORT_CSV_ALL}></option>
                    </select>
                    <input class="formButton" type="submit" name="submitbulk" value="<{$smarty.const._SUBMIT}>"
                           title="<{$smarty.const._SUBMIT}>">
                </div>
            </form>
        <{/if}>
        <form name="select"
              action="logs.php?op=<{if $query_page}>&amp;<{$query_page}><{/if}><{if $query_entry}><{$query_entry}><{/if}>"
              method="POST"
              onsubmit="if(window.document.select.op.value =='') {return false;} else if (window.document.select.op.value =='del') {return deleteSubmitValid('log_id[]');} else if (isOneChecked('log_id[]')) {return true;} else {alert('<{$smarty.const._AM_USERLOG_LOG_ERRORSELECT}>'); return false;};">
            <input type="hidden" name="confirm" value="1">
            <div class="floatleft">
                <a href="#submitDown"><img src="<{xoModuleIcons16 down.png}>" alt="<{$smarty.const._AM_USERLOG_DOWN}>"
                                           title="<{$smarty.const._AM_USERLOG_DOWN}>"></a>
                <select name="op">
                    <option value=""><{$smarty.const._AM_USERLOG_LOG_SELECT}></option>
                    <option value="del"><{$smarty.const._AM_USERLOG_LOG_DELETE_SELECT}></option>
                    <option value="export-csv"><{$smarty.const._AM_USERLOG_LOG_EXPORT_CSV_SELECT}></option>
                </select>
                <input id="submitUp" class="formButton" type="submit" name="submitselect"
                       value="<{$smarty.const._SUBMIT}>" title="<{$smarty.const._SUBMIT}>">
            </div>
            <{$pagenav}>
            <div class="clear"></div>
            <div class="head boxshadow1 border x-small">
                <div class="width1 floatleft center">
                    <{$smarty.const._ALL}>:
                </div>
                <div class="width1 floatleft center">
                    <input title="<{$smarty.const._ALL}>" type="checkbox" name="id_check" id="id_check" value="1"
                           onclick="xoopsCheckAll('select', 'id_check');">
                </div>
                <{foreach item=title key=header from=$headers}>
                    <div title="<{$title}>"
                         class="truncate width<{if $header == "admin" || $header == "pageadmin" || $header == "log_id" || $header == "uid" || $header == "item_name" || $header == "item_id"}>1<{else}><{$widthC}><{/if}> floatleft center">
                        <a class="ui-corner-all tooltip <{$orderentry}>" title="<{$title}>"
                           href="logs.php?limitentry=<{$limitentry}>&amp;sortentry=<{$header}><{if $query_page}>&amp;<{$query_page}><{/if}><{if $sortentry eq $header}>&amp;orderentry=<{if $orderentry eq 'DESC'}>ASC<{else}>DESC<{/if}><{/if}> "
                           alt="<{$title}>"><{if $sortentry eq $header}><img
                                src="<{xoModuleIcons16 DESC.png}>"><{/if}><{$title}></a>
                    </div>
                <{/foreach}>
                <div class="clear"></div>
            </div>
            <{foreach item=log key=log_id from=$logs}>
                <div class="<{cycle values='even,odd'}> border x-small">
                    <div class="width1 floatleft center">
                        <input type="image" src="<{xoModuleIcons16 delete.png}>" alt="<{$smarty.const._DELETE}>"
                               title="<{$smarty.const._DELETE}>"
                               onclick="window.document.select.op.value ='del';window.document.getElementById('log_id[<{$log_id}>]').checked = true; window.document.forms.select.click();">
                    </div>
                    <div class="width1 floatleft center">
                        <input type="checkbox" name="log_id[]" id="log_id[<{$log_id}>]" value="<{$log_id}>">
                    </div>
                    <{foreach item=title key=header from=$headers}>
                        <div title="<{$log.$header}>"
                             class="edit_col truncate width<{if $header == "admin" || $header == "pageadmin" || $header == "log_id" || $header == "uid" || $header == "item_name" || $header == "item_id"}>1<{else}><{$widthC}><{/if}> floatleft center">
                            <{if $header == "uname"}>
                                <a href="<{$xoops_url}>/userinfo.php?uid=<{$log.uid}>"><{$log.uname}></a>
                            <{elseif $header == "user_ip"}>
                                <a href="http://www.whois.sc/<{$log.user_ip}>"><{$log.user_ip}></a>
                            <{elseif $header == "admin" || $header == "pageadmin"}>
                                <{if $log.$header == 1}><{$smarty.const._YES}><{else}><{$smarty.const._NO}><{/if}>
                            <{elseif $header == "referer"}>
                                <a href="<{$log.referer}>"><{$log.referer}></a>
                            <{elseif $header == "item_id"}>
                                <{if $log.item_id gt 0}><{$log.item_id}><{/if}>
                            <{else}>
                                <{$log.$header}>
                            <{/if}>
                        </div>
                    <{/foreach}>
                    <div class="clear"></div>
                </div>
            <{/foreach}>
        </form>
        <div class="floatleft">
            <a id="submitDown" href="#submitUp"><img src="<{xoModuleIcons16 up.png}>"
                                                     alt="<{$smarty.const._AM_USERLOG_UP}>"
                                                     title="<{$smarty.const._AM_USERLOG_UP}>"></a>
        </div>
        <{$pagenav}>
        <div class="clear"></div>
    </div>
<{/if}>
<{$form}>
<script type="text/javascript">
    function deleteSubmitValid($name) {
        if ($name == null || isOneChecked($name)) {
            return confirm('<{$smarty.const._AM_USERLOG_LOG_DELETE_CONFIRM}>');
        } else {
            alert('<{$smarty.const._AM_USERLOG_LOG_DELETE_ERRORSELECT}>');
            return false;
        }
        return false;
    }
</script>
