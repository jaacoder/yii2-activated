<?php

namespace Jaacoder\Yii2Activated\Controllers\Rest;

use ReflectionClass;
use Jaacoder\Yii2Activated\Controllers\Rest\Controller;
use Jaacoder\Yii2Activated\Models\ActiveRecord;
use Jaacoder\Yii2Activated\Models\Paging;
use Yii;
use yii\db\ActiveQuery;

/**
 * Class ActiveControllerPro.
 * 
 * @property ActiveRecord $model
 * @property ActiveRecord[] $models
 * @property ActiveRecord $filter
 * @property boolean $listing
 * @property Paging $paging
 * @property array $orderBy
 * @property array $ids
 */
class ActiveController extends Controller
{
    const VIEW_LISTING = 'listing';
    const VIEW_FORM = 'form';

    /**
     * @var string|ActiveRecord
     */
    protected $modelClass;

    /**
     * Model used for insert
     * @var ActiveRecord
     */
    protected $insertModel;

    /**
     * Model used for update
     * @var ActiveRecord
     */
    protected $updateModel;

    /**
     * Call actionNew() after actionInsert() ?
     * @var bool
     */
    protected $newAfterInsert = true;
    
    /**
     * search at listing view even if no search was done before
     * @var bool
     */
    protected $forceSearchOnListing;

    /**
     * Create new model instance.
     * 
     * @return ActiveRecord
     */
    public function newModelInstance()
    {
        $modelClass = $this->modelClass;
        return new $modelClass();
    }

    /**
     * Initializes the object.
     */
    public function init()
    {
        // default actions
        if (empty($this->defaultActions)) {
            $this->defaultActions = [
                'GET' => ['index', 'edit'], // empty($id) ? => 'index', not empty => 'edit'
                'POST' => 'insert',
                'PUT' => 'update',
                'PATCH' => 'update',
                'DELETE' => 'delete',
            ];
        }

        // generate the link with model class automatically
        if (empty($this->modelClass)) {
            $reflectionClass = new ReflectionClass(get_called_class());
            $controllerShortName = $reflectionClass->getShortName();
            $controllerShortNameParts = explode('Controller', $controllerShortName);
            $this->modelClass = "app\models\\" . $controllerShortNameParts[0];
        }

        parent::init();
    }

    /**
     * Default index method.
     * 
     * @return array
     */
    public function actionIndex()
    {
        return $this->actionListing();
    }
    
    /**
     * Alias to actionListing().
     * 
     * @deprecated since version 0.0.1
     * 
     * @return array
     */
    public function actionInitListing()
    {
        return $this->actionListing();
    }

    /**
     * Go to listing view.
     * 
     * @return array
     */
    public function actionListing()
    {
        // fetch paging from post data
        $paging = $this->hasProperty('paging') ? $this->paging : $this->postObject('paging', get_class(new Paging()));

        // need to search again ?
        if ($this->forceSearchOnListing || (isset($paging->lastPage) ? $paging->lastPage : null)) {
            $this->actionSearch();
            //
        } else {
            // save paging
            $this->paging = $paging;
        }
        
        // send to listing view
        $this->listing = true;
        $this->view = self::VIEW_LISTING;

        // load other objects
        $this->loadListingObjects();
    }
    
    /**
     * Alias to actionNew()
     * 
     * @deprecated since version 0.0.1
     * 
     * @return array
     */
    public function actionInitForm()
    {
        return $this->actionNew();
    }
    
    /**
     * Go to form view with new record.
     * 
     * @return array
     */
    public function actionNew()
    {
        $this->model = $this->newModelInstance();
        $this->listing = false;
        $this->view = self::VIEW_FORM;
        
        // load other objects
        $this->loadFormObjects();
    }

    /**
     * Edit model.
     * 
     * @param string $id
     * @return array
     */
    public function actionEdit($id)
    {
        $modelClass = $this->modelClass;
        $this->model = $modelClass::findOne($id);
        
        if ($this->model === null) {
            $this->addWarningMessage('Registro não encontrado!');
            return $this->actionNew();
        }
        
        // send to form view
        $this->listing = false;
        $this->view = self::VIEW_FORM;
        
        // load other objects
        $this->loadFormObjects();
    }

    /**
     * Perform search on model.
     * 
     * @param AbstractEntity $filter
     * @param Paging $paging
     * @return array
     */
    public function actionSearch()
    {
        // $filter, $paging, $oderBy = []
        $modelClass = $this->modelClass;
        
        $paging = $this->hasProperty('paging') ? $this->paging : $this->postObject('paging', get_class(new Paging()));
        $orderBy = $this->post('orderBy', []);

        $query = $modelClass::find()
            ->orderBy($orderBy);

        // include conditions, joins and classification on query
        $this->adjustSearchQuery($query);

        // prepare paging
        $this->adjustPagingBeforeQuerying($paging, $query);

        // retrieve models form db
        $this->models = $query->limit(isset($paging->perPage) ? $paging->perPage : -1)
            ->offset((isset($paging->from) ? $paging->from : 1) - 1)
            ->all();

        // adjust paging
        $this->adjustPagingAfterQuerying($paging, count($this->models));

        // alert if no models found
        if (empty($this->models)) {
            $this->addInfoMessage('Nenhum registro encontrado.');
        }
        
        // save paging
        $this->paging = $paging;
    }

    /**
     * Filter query for search.
     * 
     * @param ActiveQuery $query
     */
    public function adjustSearchQuery($query)
    {
    }

    /**
     * @param Paging $paging
     * @param ActiveQuery $query
     */
    public function adjustPagingBeforeQuerying($paging, ActiveQuery $query)
    {
        $paging->total = $query->count();

        // init current page if not set
        if (!isset($paging->currentPage)) {
            $paging->currentPage = 1;
            //
        } elseif ($paging->currentPage < 1) {   // avoid zero or negative current page
            $paging->currentPage = 1;
        }

        // calculate last page
        $paging->lastPage = ceil($paging->total / $paging->perPage);

        // current page could not be bigger than last page
        if ($paging->currentPage > $paging->lastPage && $paging->lastPage > 0) {
            $paging->currentPage = $paging->lastPage;
        }

        // calculate first record index
        $paging->from = 0;
        if ($paging->total > 0) {
            $paging->from = ($paging->currentPage - 1) * $paging->perPage + 1;
        }
    }

    /**
     * @param Paging $paging
     * @param int $currentPageSize
     */
    public function adjustPagingAfterQuerying($paging, $currentPageSize)
    {
        $paging->currentPageSize = $currentPageSize;
        $paging->to = max([$paging->from + $currentPageSize - 1, 0]);
    }

    /**
     * Insert model.
     */
    public function actionInsert()
    {
        // if no insertModel, create one to use
        if (!$this->insertModel) {
            $this->insertModel = $this->newModelInstance();
            $this->insertModel->attributes = $this->post(null, []);
        }

        $this->insertModel->insert();

        // insert message
        $this->addInsertMessage();

        // clear form ?
        if ($this->newAfterInsert) {
            $this->actionNew();
        }
    }

    /**
     * Update model.
     */
    public function actionUpdate($id)
    {
        // if no updateModel, fetch from database
        if (!$this->updateModel) {
            $modelClass = $this->modelClass;
            $this->updateModel = $modelClass::findOne($id);
            
            // fill with post data
            $this->updateModel->attributes = Yii::$app->request->post(null, []);
        }

        // save
        $this->updateModel->update();

        // update message
        $this->addUpdateMessage();
    }
    
    /**
     * Delete model.
     * 
     * @param string $id
     */
    public function actionDelete($id)
    {
        $modelClass = $this->modelClass;
        $modelClass::findOne($id)->delete();
        
        $this->addDeleteMessage();
    }
    
    /**
     * Delete all models.
     * 
     * @param array $ids
     * @return array|null
     */
    public function actionDeleteAll()
    {
        $ids = $this->post();
        if (!$ids) {
            $ids = [];
        }
                
        if (empty($ids)) {
            $this->addWarningMessage('Nenhum registro selecionado!');
            return;
        }
        
        $modelClass = $this->modelClass;
        
        /* @var $model ActiveRecord */
        foreach ($modelClass::findAll($ids) as $model) {
            $model->delete();
        }
        
        $this->addSuccessMessage('Registro(s) removido(s)!');
        $this->ids = [];
    }

    /**
     * Add insert message.
     */
    public function addInsertMessage()
    {
        $this->addSuccessMessage('Registro inserido!');
    }

    /**
     * Add insert message.
     */
    public function addUpdateMessage()
    {
        $this->addSuccessMessage('Registro alterado!');
    }

    /**
     * Add deleting message.
     */
    public function addDeleteMessage()
    {
        $this->addSuccessMessage('Registro removido!');
    }

    /**
     * Load objects for listing
     */
    public function loadListingObjects()
    {
    }

    /**
     * Load objects for form
     */
    public function loadFormObjects()
    {
    }

    /**
     * Previous model.
     * @param string $id
     */
    public function actionPrevious($id)
    {
        $modelClass = $this->modelClass;
        $pk = $modelClass::primaryKey()[0];
        
        $model = $modelClass::find()
                ->select($pk)
                ->where(['<', $pk, $id])
                ->orderBy([$pk => SORT_DESC])
                ->one();
        
        if ($model) {
            $this->actionEdit($model->$pk);
        } else {
            $this->addWarningMessage('Registro não encontrado!');
        }
    }
    
    /**
     * Previous model.
     * @param string $id
     */
    public function actionNext($id)
    {
        $modelClass = $this->modelClass;
        $pk = $modelClass::primaryKey()[0];
        
        $model = $modelClass::find()
                ->select($pk)
                ->where(['>', $pk, $id])
                ->orderBy([$pk => SORT_ASC])
                ->one();
        
        if ($model) {
            $this->actionEdit($model->$pk);
        } else {
            $this->addWarningMessage('Registro não encontrado!');
        }
    }
}
