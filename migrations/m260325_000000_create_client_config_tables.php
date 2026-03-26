<?php

use yii\db\Migration;

/**
 * Creates the client_config and client_config_history tables.
 */
class m260325_000000_create_client_config_tables extends Migration
{
    public function up()
    {
        // Main config table — one row per customer
        $this->createTable('{{%client_config}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull()->unique(),
            'content' => $this->text()->defaultValue(''),
            'updated_by' => $this->integer()->defaultValue(0),
            'updated_by_name' => $this->string(255)->defaultValue(''),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);

        $this->createIndex('idx_client_config_customer', '{{%client_config}}', 'customer_id');

        // Audit history — one row per edit
        $this->createTable('{{%client_config_history}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull(),
            'content_before' => $this->text(),
            'content_after' => $this->text(),
            'changed_by' => $this->integer()->notNull(),
            'changed_by_name' => $this->string(255)->notNull(),
            'created_at' => $this->dateTime(),
        ]);

        $this->createIndex('idx_client_config_history_customer', '{{%client_config_history}}', 'customer_id');
        $this->createIndex('idx_client_config_history_date', '{{%client_config_history}}', 'created_at');
    }

    public function down()
    {
        $this->dropTable('{{%client_config_history}}');
        $this->dropTable('{{%client_config}}');
    }
}
