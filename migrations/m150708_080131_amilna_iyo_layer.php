<?php

use yii\db\Schema;
use yii\db\Migration;

class m150708_080131_amilna_iyo_layer extends Migration
{
    public function safeUp()
    {
		 $this->createTable($this->db->tablePrefix.'iyo_layer', [
            'id' => 'pk',
            'data_id' => Schema::TYPE_INTEGER. '',
            'title' => Schema::TYPE_STRING . '(65) NOT NULL',
            'description' => Schema::TYPE_STRING . '(155) NOT NULL',
            'remarks' => Schema::TYPE_TEXT . '',            
            'config' => Schema::TYPE_TEXT . ' NOT NULL',
            'tags' => Schema::TYPE_STRING . '',            
            'author_id' => Schema::TYPE_INTEGER,            
            'type' => Schema::TYPE_SMALLINT. ' NOT NULL DEFAULT 0',
            'status' => Schema::TYPE_SMALLINT. ' NOT NULL DEFAULT 1',
            'time' => Schema::TYPE_TIMESTAMP. ' NOT NULL DEFAULT NOW()',
            'isdel' => Schema::TYPE_SMALLINT.' NOT NULL DEFAULT 0',
        ]);
        $this->addForeignKey( $this->db->tablePrefix.'iyo_layer_author_id', $this->db->tablePrefix.'iyo_layer', 'author_id', $this->db->tablePrefix.'user', 'id', 'SET NULL', null );
        $this->addForeignKey( $this->db->tablePrefix.'iyo_layer_data_id', $this->db->tablePrefix.'iyo_layer', 'data_id', $this->db->tablePrefix.'iyo_data', 'id', 'SET NULL', null );
        
        $this->createTable($this->db->tablePrefix.'iyo_map_lay', [                        
            'map_id' => Schema::TYPE_INTEGER. ' NOT NULL',
            'layer_id' => Schema::TYPE_INTEGER. ' NOT NULL',            
            'isdel' => Schema::TYPE_SMALLINT.' NOT NULL DEFAULT 0',
        ]);
        $this->addForeignKey( $this->db->tablePrefix.'iyo_map_lay_map_id', $this->db->tablePrefix.'iyo_map_lay', 'map_id', $this->db->tablePrefix.'iyo_map', 'id', 'CASCADE', null );
        $this->addForeignKey( $this->db->tablePrefix.'iyo_map_lay_layer_id', $this->db->tablePrefix.'iyo_map_lay', 'layer_id', $this->db->tablePrefix.'iyo_layer', 'id', 'CASCADE', null );
        
        $this->createTable($this->db->tablePrefix.'iyo_lay_dat', [                                    
            'layer_id' => Schema::TYPE_INTEGER. ' NOT NULL',            
            'data_id' => Schema::TYPE_INTEGER. ' NOT NULL',
            'isdel' => Schema::TYPE_SMALLINT.' NOT NULL DEFAULT 0',
        ]);        
        $this->addForeignKey( $this->db->tablePrefix.'iyo_lay_dat_layer_id', $this->db->tablePrefix.'iyo_lay_dat', 'layer_id', $this->db->tablePrefix.'iyo_layer', 'id', 'CASCADE', null );
        $this->addForeignKey( $this->db->tablePrefix.'iyo_lay_dat_data_id', $this->db->tablePrefix.'iyo_lay_dat', 'data_id', $this->db->tablePrefix.'iyo_data', 'id', 'CASCADE', null );
    }

    public function safeDown()
    {
        echo "m150708_080131_amilna_iyo_layer cannot be reverted.\n";

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
