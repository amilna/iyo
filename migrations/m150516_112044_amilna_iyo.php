<?php

use yii\db\Schema;
use yii\db\Migration;

class m150516_112044_amilna_iyo extends Migration
{
    public function safeUp()
    {
		$this->createTable($this->db->tablePrefix.'iyo_category', [
            'id' => 'pk',            
            'title' => Schema::TYPE_STRING . '(65) NOT NULL',
            'parent_id' => Schema::TYPE_INTEGER,
            'description' => Schema::TYPE_TEXT.' NOT NULL',
            'image' => Schema::TYPE_STRING.'',
            'status' => Schema::TYPE_BOOLEAN.' NOT NULL DEFAULT TRUE',
            'isdel' => Schema::TYPE_SMALLINT.' NOT NULL DEFAULT 0',
        ]);
        $this->createIndex($this->db->tablePrefix.'iyo_category_title'.'_key', $this->db->tablePrefix.'iyo_category', 'title', true);        
        $this->addForeignKey( $this->db->tablePrefix.'iyo_category_parent_id', $this->db->tablePrefix.'iyo_category', 'parent_id', $this->db->tablePrefix.'iyo_category', 'id', 'SET NULL', null );
        
        $this->createTable($this->db->tablePrefix.'iyo_data', [
            'id' => 'pk',
            'title' => Schema::TYPE_STRING . '(65) NOT NULL',
            'description' => Schema::TYPE_STRING . '(155) NOT NULL',
            'remarks' => Schema::TYPE_TEXT . '',            
            'metadata' => Schema::TYPE_TEXT . ' NOT NULL',
            'tags' => Schema::TYPE_STRING . '',            
            'author_id' => Schema::TYPE_INTEGER,
            'type' => Schema::TYPE_SMALLINT. ' NOT NULL DEFAULT 0',
            'status' => Schema::TYPE_SMALLINT. ' NOT NULL DEFAULT 1',
            'time' => Schema::TYPE_TIMESTAMP. ' NOT NULL DEFAULT NOW()',
            'isdel' => Schema::TYPE_SMALLINT.' NOT NULL DEFAULT 0',
        ]);
        $this->addForeignKey( $this->db->tablePrefix.'iyo_data_author_id', $this->db->tablePrefix.'iyo_data', 'author_id', $this->db->tablePrefix.'user', 'id', 'SET NULL', null );
        
        $this->createTable($this->db->tablePrefix.'iyo_map', [
            'id' => 'pk',
            'title' => Schema::TYPE_STRING . '(65) NOT NULL',
            'description' => Schema::TYPE_STRING . '(155) NOT NULL',
            'remarks' => Schema::TYPE_TEXT . '',            
            'config' => Schema::TYPE_TEXT . ' NOT NULL',
            'tags' => Schema::TYPE_STRING . '',            
            'author_id' => Schema::TYPE_INTEGER,            
            'status' => Schema::TYPE_SMALLINT. ' NOT NULL DEFAULT 1',
            'time' => Schema::TYPE_TIMESTAMP. ' NOT NULL DEFAULT NOW()',
            'isdel' => Schema::TYPE_SMALLINT.' NOT NULL DEFAULT 0',
        ]);
        $this->addForeignKey( $this->db->tablePrefix.'iyo_map_author_id', $this->db->tablePrefix.'iyo_map', 'author_id', $this->db->tablePrefix.'user', 'id', 'SET NULL', null );
        
        $this->createTable($this->db->tablePrefix.'iyo_cat_map', [                        
            'category_id' => Schema::TYPE_INTEGER. ' NOT NULL',
            'map_id' => Schema::TYPE_INTEGER. ' NOT NULL',            
            'isdel' => Schema::TYPE_SMALLINT.' NOT NULL DEFAULT 0',
        ]);
        $this->addForeignKey( $this->db->tablePrefix.'iyo_cat_map_category_id', $this->db->tablePrefix.'iyo_cat_map', 'category_id', $this->db->tablePrefix.'iyo_category', 'id', 'CASCADE', null );
        $this->addForeignKey( $this->db->tablePrefix.'iyo_cat_map_map_id', $this->db->tablePrefix.'iyo_cat_map', 'map_id', $this->db->tablePrefix.'iyo_map', 'id', 'CASCADE', null );               
    }

    public function safeDown()
    {
        echo "m150516_112044_amilna_iyo cannot be reverted.\n";

        return false;
    }
    
    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }
    
    public function safeDown()
    {
    }
    */
}
