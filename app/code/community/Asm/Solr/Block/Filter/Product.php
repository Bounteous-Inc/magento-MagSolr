<?php
/**
 * Created by PhpStorm.
 * User: ian
 * Date: 8/28/14
 * Time: 12:30 PM
 */
class Asm_Solr_Block_Filter_Product extends Asm_Solr_Block_Filter
{
    protected $_type        = 'product';
    protected $_solrType    = 'catalog/product';

    public function getResultLink()
    {
        $query = array("q" => $this->getRequest()->getParam("q"));
        return $this->getUrl('*/*/'.$this->getType(), array('_query' => $query));
    }

    public function getType()
    {
        return $this->_type;
    }

    public function getSolrType()
    {
        return $this->_solrType;
    }

    public function getTitle(){
        $title = 'Product Results';

        return $title;
    }

    public function getResultCount()
    {
        return $this->getResult()->getCount();
    }

    public function getResult()
    {
        // if we don't have a result set for us, let's make one
        if (!$this->getData('result'))
        {
            /** @var Asm_Solr_Model_Result $result */
            $result = Mage::getModel('solr/result');

            $keywords = $this->getKeywords();
            $filteredQuery = $this->getFilteredQuery();

            $solrType = 'catalog/product';
            $limit = 0;
            $offset = 0;

            $query = $result->getQuery();
            $query->setFaceting(true);
            $query->setKeywords($keywords);
            $query->addFilter('type', $solrType);

            $query->addQueryParameter("facet.range","price");
            $query->addQueryParameter("facet.range.start","0");
            $query->addQueryParameter("facet.range.end","100");
            $query->addQueryParameter("facet.range.gap","10");
            $query->addQueryParameter("facet.field","categoryId");
            $query->addQueryParameter("facet.field","tcm_functions_stringM");

            if($filteredQuery!='' or $filteredQuery!=null)
            {
                foreach(explode(',',$filteredQuery) as $fq)
                {
                    $query->addQueryParameter("fq", $fq);
                }
            }

            $result->load($limit, $offset);

            $this->setData('result', $result);
        }

        return $this->getData('result');
    }

    public function getFacetRanges()
    {
        return $this->getResult()->getResponse()->getFacetRanges();
    }

    public function getFacetFields()
    {
        return $this->getResult()->getResponse()->getFacetField();
    }

    function getAllCategories($ids)
    {
        $data = array();

        if (!empty($ids))
        {
            foreach ($ids as $id=>$occurrences)
            {
                $cat = Mage::getModel('catalog/category');
                $cat->load($id);
                $categoryData = array('id'=>$id,
                    'url'=>$cat->getUrl(),
                    'name'=>$cat->getName(),
                    'occurrences'=>$occurrences,
                    'isActive'=>$cat->getIsActive()
                );
                array_push($data, $categoryData);
            }
        }

        return $data;
    }

    public function getPriceFilteredLink($param1,$param2)
    {
        $params = $this->getRequest()->getParams();
        $fq = "";

        if(array_key_exists("fq",$params))
        {
            $fq = $params["fq"].',';
        }

        $params["fq"]=$fq.'price:['. $param1 .' TO '. $param2 .']';
        $url = $this->getUrl('*/*/'.$this->getType(), array('_query' => $params));

        return $url;
    }

    public function getFacetFieldFilteredLink($type, $id)
    {
        $params = $this->getRequest()->getParams();
        $fq = "";

        if(array_key_exists("fq",$params))
        {
            $fq = $params["fq"].',';
        }

        $params["fq"]=$fq.$type.':'.$id;
        $url = $this->getUrl('*/*/'.$this->getType(), array('_query' => $params));

        return $url;
    }

    public function getCurrentFilters()
    {
        $params = $this->getRequest()->getParams();
        $labels = array();
        $labels['fq']=array();
        $labels['name']=array();

        if(array_key_exists("fq",$params))
        {
            $fq = $params["fq"];
            $fq_arr = explode(",",$fq);

            foreach($fq_arr as $key=>$f)
            {
                $type = explode(":", $f);
                $labels['fq'][$key] = $f;

                if($type[0] == "price")
                {
                    $r = explode(" TO ",substr($type[1],1,-1));
                    $labels['name'][$key] = '$'.$r[0].' - $'.$r[1];
                }
                elseif($type[0] == "categoryId")
                {
                    $category = $this->getAllCategories(array($type[1]=>null));
                    $labels['name'][$key] = $category[0]['name'];
                }
                elseif($type[0] == "tcm_functions_stringM")
                {
                    $labels['name'][$key] = substr($type[1],1,-1);
                    $test='bob';
                }
            }
        }
        else
        {
            return array("name"=>array(0=>"No Filters"),"fq"=>array(0=>""));
        }

        return $labels;
    }

    public function deleteFilter($filter)
    {
        $params = $this->getRequest()->getParams();

        if(array_key_exists("fq",$params))
        {
            $fq = explode(",",$params["fq"]);
            unset($fq[$filter]);
            $fq = array_values($fq);
            $params["fq"] = implode(",",$fq);

            if($params["fq"]=="")
            {
                unset($params["fq"]);
            }

            $url = $this->getUrl('*/*/'.$this->getType(), array('_query' => $params));

            return $url;
        }

        return null;
    }

}