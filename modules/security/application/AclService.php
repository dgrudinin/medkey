<?php
namespace app\modules\security\application;

use app\common\acl\resource\ApplicationResourceInterface;
use app\common\acl\resource\ResourceInterface;
use app\common\base\Model;
use app\common\data\ActiveDataProvider;
use app\common\db\ActiveRecord;
use app\common\dto\Dto;
use app\common\helpers\ClassHelper;
use app\common\helpers\CommonHelper;
use app\common\helpers\Json;
use app\common\service\ApplicationService;
use app\common\service\exception\AccessApplicationServiceException;
use app\common\service\exception\ApplicationServiceException;
use app\modules\security\models\orm\Acl;
use app\modules\security\models\orm\AclRole;
use app\modules\security\models\orm\User;
use yii\base\InvalidValueException;

/**
 * Class AclService
 * @package Module\Security
 * @copyright 2012-2019 Medkey
 */
class AclService extends ApplicationService implements AclServiceInterface, ApplicationResourceInterface
{
    /**
     * @deprecated
     * @var string
     */
    public $modelClass = Acl::class;

    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub
    }

    /**
     * @inheritdoc
     */
    public function aclAlias()
    {
        return 'ACL';
    }

    /**
     * @inheritdoc
     */
    public function getPrivileges()
    {
        return [
            'add' => 'Создание ACL',
            'update' => 'Обновление ACL',
            'deleteAcl' => 'Удаление ACL',
            'getAclList' => 'Список ACL',
            'getAclRoleList' => 'Список ролей',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAclList(Model $form)
    {
        if (!$this->isAllowed('getAclList')) {
            throw new AccessApplicationServiceException('Доступ к списку ACL запрещён.');
        }
        $query = Acl::find();
        $query
            ->joinWith(['aclRole'])
            ->andFilterWhere([
                'cast(updated_at as date)' =>
                    empty($form->updatedAt) ? null : \Yii::$app->formatter->asDate($form->updatedAt, CommonHelper::FORMAT_DATE_DB),
            ]);
        return new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'attributes' => [
                    'type',
                    'type_acl',
                    'module',
                    'entity_type',
                    'action',
                    'updated_at',
                    'aclRole.name' => [
                        'asc' => [
                            'acl_role.name' => SORT_ASC,
                        ],
                        'desc' => [
                            'acl_role.name' => SORT_DESC,
                        ],
                    ]
                ],
            ],
            'pagination' => [
                'pageSize' => 10
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getAclRoleList(Model $form)
    {
        if (!$this->isAllowed('getAclRoleList')) {
            throw new AccessApplicationServiceException('Доступ к списку ролей запрещён.');
        }
        $query = AclRole::find();
        $query
            ->andFilterWhere([
                'cast(updated_at as date)' =>
                    empty($form->updatedAt) ? null : \Yii::$app->formatter->asDate($form->updatedAt, CommonHelper::FORMAT_DATE_DB),
            ]);
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10
            ],
        ]);
    }

    /**
     * @param Dto $aclDto
     * @param string $scenario
     * @return ActiveRecord
     * @throws \Exception
     */
    public function add($aclDto, $scenario = ActiveRecord::SCENARIO_CREATE)
    {
        if (!$this->isAllowed('add')) {
            throw new AccessApplicationServiceException('Доступ к созданию ACL запрещён.');
        }
        $modelClass = $this->modelClass;
        if (!($aclDto instanceof Dto)) {
            throw new InvalidValueException('object is not instance Dto class'); // todo normalize text
        }
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            /** @var ActiveRecord $model */
            $model = new $modelClass([
                'scenario' => $scenario
            ]);
            $model->loadDto($aclDto);
            if (!$model->save()) {
                $errors = Json::encode($model->getErrors());
                throw new ApplicationServiceException('Не удалось сохранить ACL. Причина: ' . $errors);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $model;
    }

    /**
     * @param string $id
     * @param Dto $aclDto
     * @param string $scenario
     * @return ActiveRecord
     * @throws \Exception
     */
    public function update($id, $aclDto, $scenario = ActiveRecord::SCENARIO_UPDATE)
    {
        if (!$this->isAllowed('update')) {
            throw new AccessApplicationServiceException('Доступ к обновлению ACL запрещён.');
        }
        $modelClass = $this->modelClass;
        if (!($aclDto instanceof Dto)) {
            throw new InvalidValueException('object is not instance Dto class'); // todo normalize text
        }
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            /** @var ActiveRecord $modelClass */
            $model = $modelClass::findOneEx($id);
            $model->setScenario($scenario);
            $model->loadDto($aclDto);
            if (!$model->save()) {
                $errors = Json::encode($model->getErrors());
                throw new ApplicationServiceException('Не удалось сохранить ACL. Причина: ' . $errors);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $model;
    }

    public function deleteAcl($id)
    {
        if (!$this->isAllowed('deleteAcl')) {
            throw new AccessApplicationServiceException('Доступ к обновлению ACL запрещён.');
        }
        $acl = Acl::findOneEx($id);
        return $acl->deleteHistory();
    }

    /**
     * @todo перенести в доменный сервис -> доменный объект
     */
    public function getAclRuleByResource($resource, $action, $entityId = null, $type = Acl::TYPE_SERVICE)
    {
        if (\Yii::$app->user->isGuest) {
            throw new AccessApplicationServiceException('Требуется аутентификация.');
        }
        $user = User::findOneEx(\Yii::$app->user->id);
        $role = $user->aclRole;
        if (null === $role) {
            throw new AccessApplicationServiceException('У пользователя не найдена роль.');
        }
        if (!$resource instanceof ResourceInterface) {
            throw new AccessApplicationServiceException('Текущий объект не является ресурсом ACL.');
        }
        $acl = Acl::find()
            ->where([
                'module' => ClassHelper::getMatchModule($resource, false),
                'entity_type' => ClassHelper::getShortName($resource),
                'action' => $action,
                'type' => $type,
                'acl_role_id' => $role->id,
            ])
            ->andFilterWhere([
                'entity_id' => $entityId,
            ])
            ->one();
        if (!isset($acl)) {
            throw new AccessApplicationServiceException('Не найден ACL по заданному критерию.');
        }
        return $acl->rule;
    }
}