<?php

namespace splynx\client_config\controllers;

use splynx\client_config\models\ClientConfig;
use splynx\client_config\models\ClientConfigHistory;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use Yii;

/**
 * Handles the Client Config tab within the customer detail page.
 */
class CustomerController extends Controller
{
    /**
     * @return array<string, string|array<string,mixed>>
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Display the rich text editor tab for a customer.
     */
    public function actionIndex(int $customerId)
    {
        $model = ClientConfig::findOrCreate($customerId);
        $history = ClientConfigHistory::find()
            ->where(['customer_id' => $customerId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(50)
            ->all();

        return $this->render('index', [
            'model' => $model,
            'customerId' => $customerId,
            'history' => $history,
        ]);
    }

    /**
     * Save config content via AJAX. Creates an audit trail entry.
     */
    public function actionSave(int $customerId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        if (!$request->isPost) {
            return ['success' => false, 'message' => 'POST required'];
        }

        $newContent = self::sanitizeHtml($request->post('content', ''));
        $admin = Yii::$app->user->identity;
        $adminId = $admin ? $admin->id : 0;
        $adminName = $admin ? $admin->name : 'Unknown';

        $model = ClientConfig::findOrCreate($customerId);
        $oldContent = $model->content;

        // Skip if nothing changed
        if ($oldContent === $newContent) {
            return [
                'success' => true,
                'message' => 'No changes detected',
                'updated_at' => $model->updated_at,
                'updated_by_name' => $model->updated_by_name,
            ];
        }

        // Save the new content
        $model->content = $newContent;
        $model->updated_by = $adminId;
        $model->updated_by_name = $adminName;

        if (!$model->save()) {
            return ['success' => false, 'message' => 'Failed to save', 'errors' => $model->errors];
        }

        // Record audit history
        $historyEntry = new ClientConfigHistory();
        $historyEntry->customer_id = $customerId;
        $historyEntry->content_before = $oldContent;
        $historyEntry->content_after = $newContent;
        $historyEntry->changed_by = $adminId;
        $historyEntry->changed_by_name = $adminName;
        $historyEntry->save();

        return [
            'success' => true,
            'message' => 'Saved successfully',
            'updated_at' => $model->updated_at,
            'updated_by_name' => $model->updated_by_name,
            'history_id' => $historyEntry->id,
        ];
    }

    /**
     * Return paginated history as JSON (for lazy-loading more entries).
     */
    public function actionHistory(int $customerId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $page = Yii::$app->request->get('page', 1);
        $perPage = 20;

        $query = ClientConfigHistory::find()
            ->where(['customer_id' => $customerId])
            ->orderBy(['created_at' => SORT_DESC]);

        $total = $query->count();
        $entries = $query->offset(($page - 1) * $perPage)->limit($perPage)->all();

        $rows = [];
        foreach ($entries as $entry) {
            $rows[] = [
                'id' => $entry->id,
                'changed_by_name' => $entry->changed_by_name,
                'created_at' => $entry->created_at,
                'content_before' => $entry->content_before,
                'content_after' => $entry->content_after,
            ];
        }

        return [
            'total' => (int) $total,
            'page' => (int) $page,
            'per_page' => $perPage,
            'entries' => $rows,
        ];
    }

    /**
     * Strip dangerous tags/attributes while keeping Quill's safe HTML output.
     */
    private static function sanitizeHtml(string $html): string
    {
        $allowedTags = '<p><br><strong><em><u><s><a><ul><ol><li>'
            . '<h1><h2><h3><blockquote><pre><code><img><span><sub><sup>';

        $clean = strip_tags($html, $allowedTags);

        // Remove event handler attributes (onclick, onerror, etc.) and javascript: URLs
        $clean = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $clean);
        $clean = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', '', $clean);
        $clean = preg_replace('/src\s*=\s*["\']javascript:[^"\']*["\']/i', '', $clean);

        return $clean;
    }
}
