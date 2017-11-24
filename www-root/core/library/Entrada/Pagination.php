<?php
/**
 * Entrada_Pagination
 *
 * The Entrada Pagination creates the pagination links for queries with too many results and allows to divide the rows in pages depends on the results per page.
 *
 */
class Entrada_Pagination
{
    var $currentPage,
        $itemCount,
        $itemsPerPage,
        $linksHref,
        $linksToDisplay,
        $pageJumpBack,
        $pageJumpNext,
        $pageSeparator,
        $queryString,
        $queryStringVar;

    function __construct($current_page = 1, $per_page = 10, $item_count = 0, $links_href = "", $query_string = "", $query_string_var = "") {
        if(!$current_page = (int) $current_page) {
            $current_page = 1;
        }
        if(!$per_page = (int) $per_page) {
            $per_page = 10;
        }
        if(!$item_count = (int) $item_count) {
            $item_count = 0;
        }
        if(!trim($links_href)) {
            $links_href = $_SERVER["PHP_SELF"];
        }
        if(!trim($query_string)) {
            $query_string = $_SERVER["QUERY_STRING"];
        }

        $this->SetCurrentPage($current_page);
        $this->SetItemsPerPage($per_page);
        $this->SetItemCount($item_count);
        $this->SetLinksFormat("&laquo;", " ", "&raquo;");
        $this->SetLinksHref($links_href);
        $this->SetLinksToDisplay(5);
        if(trim($query_string_var) == "") {
            $this->SetQueryStringVar("pv");
        } else {
            $this->SetQueryStringVar($query_string_var);
        }
        $this->SetQueryString($query_string);

        if(isset($_GET[$this->queryStringVar]) && ((int) trim($_GET[$this->queryStringVar]))){
            $this->SetCurrentPage((int) trim($_GET[$this->queryStringVar]));
        }
    }

    function SetCurrentPage($reqCurrentPage) {
        $this->currentPage = (integer) abs($reqCurrentPage);
    }

    function SetItemCount($reqItemCount) {
        $this->itemCount = (integer) abs($reqItemCount);
    }

    function SetItemsPerPage($reqItemsPerPage) {
        $this->itemsPerPage = (integer) abs($reqItemsPerPage);
    }

    function SetLinksHref($reqLinksHref) {
        $this->linksHref = $reqLinksHref;
    }

    function SetLinksFormat($reqPageJumpBack, $reqPageSeparator, $reqPageJumpNext) {
        $this->pageJumpBack = $reqPageJumpBack;
        $this->pageSeparator = $reqPageSeparator;
        $this->pageJumpNext = $reqPageJumpNext;
    }

    function SetLinksToDisplay($reqLinksToDisplay) {
        $this->linksToDisplay  = (integer) abs($reqLinksToDisplay);
    }

    function SetQueryStringVar($reqQueryStringVar) {
        $this->queryStringVar = $reqQueryStringVar;
    }

    function SetQueryString($reqQueryString) {
        $this->queryString = $reqQueryString;
    }

    function GetCurrentCollection($reqCollection) {
        if($this->currentPage < 1){
            $start = 0;
        }
        elseif($this->currentPage > $this->GetPageCount()){
            $start = $this->GetPageCount() * $this->itemsPerPage - $this->itemsPerPage;
        }
        else {
            $start = $this->currentPage * $this->itemsPerPage - $this->itemsPerPage;
        }

        return array_slice($reqCollection, $start, $this->itemsPerPage);
    }

    function GetPageCount() {
        return (integer)ceil($this->itemCount/$this->itemsPerPage);
    }

    function GetPageLinks() {
        $strLinks = '';
        $pageCount = $this->GetPageCount();
        $queryString = $this->GetQueryString();
        $linksPad = floor($this->linksToDisplay/2);

        if($this->linksToDisplay == -1){
            $this->linksToDisplay = $pageCount;
        }

        if($pageCount == 0){
            $strLinks = "1";
        }
        elseif($this->currentPage - 1 <= $linksPad || ($pageCount - $this->linksToDisplay + 1 == 0) || $this->linksToDisplay > $pageCount){
            $start = 1;
        }
        elseif($pageCount - $this->currentPage <= $linksPad){
            $start = $pageCount - $this->linksToDisplay + 1;
        }
        else {
            $start = $this->currentPage - $linksPad;
        }


        if(isset($start)){
            if($start > 1){
                if(!empty($this->pageJumpBack)){
                    $pageNum = $this->currentPage - 1;
                    if($pageNum < 1){
                        $pageNum = 1;
                    }

                    $strLinks .= "<li><a href=\"".$this->linksHref.$queryString.$pageNum."\">".$this->pageJumpBack."</a></li>".$this->pageSeparator;
                }

                $strLinks .= "<li><a href=\"".$this->linksHref.$queryString."1\">1&hellip;</a></li>".$this->pageSeparator;
            }


            if($start + $this->linksToDisplay > $pageCount){
                $end = $pageCount;
            }
            else {
                $end = $start + $this->linksToDisplay - 1;
            }


            for($i = $start; $i <= $end; $i ++){
                if($i != $this->currentPage){
                    $strLinks .= "<li><a href=\"".$this->linksHref.$queryString.($i)."\">".($i)."</a></li>".$this->pageSeparator;
                }
                else {
                    $strLinks .= "<li class=\"active\"><a href=\"#\">".$i."</a></li>".$this->pageSeparator;
                }
            }
            $strLinks = substr($strLinks, 0, -strlen($this->pageSeparator));


            if($this->currentPage < $pageCount){
                if($start + $this->linksToDisplay - 1 < $pageCount) {
                    $strLinks .= $this->pageSeparator."<li><a href=\"".$this->linksHref.$queryString.$pageCount."\">&hellip;".$pageCount."</a></li>".$this->pageSeparator;
                } else {
                    $strLinks .= $this->pageSeparator;
                }
                if(!empty($this->pageJumpNext)){
                    $pageNum = $this->currentPage + 1;
                    if($pageNum > $pageCount){
                        $pageNum = $pageCount;
                    }

                    $strLinks .= "<li><a href=\"".$this->linksHref.$queryString.$pageNum."\">".$this->pageJumpNext."</a></li>";
                }
            }
        }

        return $strLinks;
    }

    function GetPageBar($size = "normal", $alignment = "right", $margin = true) {
        $strbar = "";
        $strbar = "<div " . ($margin ? "" : "style=\"margin-top: 0; margin-bottom:0;\"") . " class=\"pagination pagination-"
                    . $size . " pagination-"
                    . $alignment . "\">
                    <ul>" . $this->GetPageLinks() . "</ul>
                  </div>";
        return $strbar;
    }

    function GetResultsLabel($query = "") {
        $limit_parameter 	= (int) (($this->itemsPerPage * $this->currentPage) - $this->itemsPerPage);
        return "<p class=\"muted text-center\">
                    <small><strong>".$this->itemCount."</strong> Result".(($this->itemCount != 1) ? "s" : "")." Found. Results ".($limit_parameter + 1)." - ".((($this->itemsPerPage + $limit_parameter) <= $this->itemCount) ? ($this->itemsPerPage + $limit_parameter) : $this->itemCount).($query != "" ? " for &quot;<strong>".$query."</strong>&quot;" : "")." shown below.\n
                    </small>
                </p>";
    }

    function GetQueryString() {
        $pattern = array("/".$this->queryStringVar."=[^&]*&?/", "/&$/");
        $replace = array("", "");
        $queryString = preg_replace($pattern, $replace, $this->queryString);
        $queryString = str_replace("&", "&amp;", $queryString);

        if(!empty($queryString)){
            $queryString.= "&amp;";
        }

        return "?".$queryString.$this->queryStringVar."=";
    }

    function GetSqlLimit() {
        return " LIMIT ".($this->currentPage * $this->itemsPerPage - $this->itemsPerPage).", ".$this->itemsPerPage;
    }
}