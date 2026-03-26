<?php

namespace splynx\client_config\models;

use yii\db\ActiveRecord;

/**
 * Audit trail — one row per save, storing who changed what and when.
 *
 * @property int    $id
 * @property int    $customer_id
 * @property string $content_before  HTML content before the change
 * @property string $content_after   HTML content after the change
 * @property int    $changed_by      Admin user ID
 * @property string $changed_by_name Admin display name
 * @property string $created_at      Timestamp of the change
 */
class ClientConfigHistory extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%client_config_history}}';
    }

    public function rules()
    {
        return [
            [['customer_id', 'changed_by', 'changed_by_name'], 'required'],
            [['customer_id', 'changed_by'], 'integer'],
            [['content_before', 'content_after'], 'string'],
            [['changed_by_name'], 'string', 'max' => 255],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'changed_by_name' => 'Changed By',
            'created_at' => 'Date',
            'content_before' => 'Before',
            'content_after' => 'After',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        return true;
    }
}
