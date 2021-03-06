<?php
namespace DataWarehouse\Query\Jobs\GroupBys;

class GroupByParentScience extends \DataWarehouse\Query\Jobs\GroupBy
{
    public static function getLabel()
    {
        return HIERARCHY_MIDDLE_LEVEL_LABEL;
    }

    public function getInfo()
    {
        return HIERARCHY_MIDDLE_LEVEL_INFO;
    }

    public function __construct()
    {
        parent::__construct(
            'parentscience',
            array(),
            'SELECT DISTINCT
                gt.parent_id AS id,
                gt.parent_description AS short_name,
                gt.parent_description AS long_name
            FROM fieldofscience_hierarchy gt
            WHERE 1
            ORDER BY gt.parent_description',
            array('fieldofscience')
        );

        $this->_id_field_name = 'parent_id';
        $this->_long_name_field_name = 'parent_description';
        $this->_short_name_field_name = 'parent_description';
        $this->_order_id_field_name = 'parent_description';
        $this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $this->fos_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'fieldofscience_hierarchy', 'fos');
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addTable($this->fos_table);

        $fos_parentscience_id_field = new \DataWarehouse\Query\Model\TableField($this->fos_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $fos_parentscience_name_field = new \DataWarehouse\Query\Model\TableField($this->fos_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $fos_parentscience_shortname_field = new \DataWarehouse\Query\Model\TableField($this->fos_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new \DataWarehouse\Query\Model\TableField($this->fos_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($fos_parentscience_name_field);
        $query->addField($fos_parentscience_id_field);
        $query->addField($fos_parentscience_shortname_field);

        $query->addGroup($fos_parentscience_id_field);

        $fostable_id_field = new \DataWarehouse\Query\Model\TableField($this->fos_table, 'id');
        $datatable_fos_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'fos_id');
        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $fostable_id_field,
                '=',
                $datatable_fos_id_field
            )
        );
        $this->addOrder($query, $multi_group);
    }

    // JMS: add join with where clause, October 2015
    public function addWhereJoin(
        \DataWarehouse\Query\Query &$query,
        \DataWarehouse\Query\Model\Table $data_table,
        $multi_group = false,
        $operation,
        $whereConstraint
    ) {
        // construct the join between the main data_table and this group by table
        $query->addTable($this->fos_table);

        $fostable_id_field = new \DataWarehouse\Query\Model\TableField($this->fos_table, 'id');
        $datatable_fos_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'fos_id');

        // the where condition that specifies the join of the tables
        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $fostable_id_field,
                '=',
                $datatable_fos_id_field
            )
        );

        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) {
            $whereConstraint = '(' . implode(',', $whereConstraint) . ')';
        }

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $fostable_id_field,
                $operation,
                $whereConstraint
            )
        );
    } // addWhereJoin

    public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
    {
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->fos_table, $this->_order_id_field_name), $dir, $this->getName());

        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, 'SELECT id FROM modw.fieldofscience_hierarchy WHERE parent_id IN (_filter_)', 'fos_id');
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            'SELECT description AS field_label FROM modw.fieldofscience_hierarchy WHERE id IN (_filter_) ORDER BY order_id'
        );
    }
}
