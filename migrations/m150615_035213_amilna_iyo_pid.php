<?php

use yii\db\Schema;
use yii\db\Migration;

class m150615_035213_amilna_iyo_pid extends Migration
{
    public function safeUp()
    {
		$this->addColumn( $this->db->tablePrefix.'iyo_data', 'pid', Schema::TYPE_INTEGER . '' );
		$this->addColumn( $this->db->tablePrefix.'iyo_data', 'srid', Schema::TYPE_INTEGER . '' );
    }

    public function safeDown()
    {
        echo "m150615_035213_amilna_iyo_pid cannot be reverted.\n";

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
