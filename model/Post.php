<?php
/**
 * 博客内容模型
 *
 */
Wind::import('component.Model');

class Post extends Model{

    protected $tableName = '{{post}}';
    protected $tablePk = 'id';
    
    private $_id;
    private $_title;
    private $_content;
    
    public function listPosts(){
        return $this->findAll();
    }
    
    public function findFirst(){
        return $this->find(1);
    }

    public function addPost($data){
        if(!is_array($data))
            return -1;

        return $this->insert($data);
    }
    
    /**
      * @return string the URL that shows the detail of the post
      */
    public function getUrl()
    {
      return WindUrlHelper::createUrl('post/view', array(
	      'id'=>$this->id,
	      'title'=>$this->title,
      ));
    }
    
    /**
      * @return array a list of links that point to the post list filtered by every tag of this post
      */
    public function getTagLinks()
    {
      $links=array();
      foreach(Tag::string2array($this->tags) as $tag)
	      $links[]=CHtml::link(CHtml::encode($tag), array('post/index', 'tag'=>$tag));
      return $links;
    }
    
    /* (non-PHPdoc)
      * @see WindEnableValidateModule::validateRules()
      */
    public function validateRules() {
	    return array(
		    WindUtility::buildValidateRule("username", "isRequired"), 
		    WindUtility::buildValidateRule("password", "isRequired"));
    }
}
