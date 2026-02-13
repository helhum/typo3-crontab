import $ from 'jquery';
import Tablesort from 'tablesort';

const Scheduler = {};

let allCheckedStatus = false;

Scheduler.actOnChangedTaskClass = (theSelector) => {
  let taskClass = theSelector.val();
  taskClass = taskClass.toLowerCase().replace(/\\/g, '-');

  $('.extraFields').hide();
  $(`.extra_fields_${taskClass}`).show();
};

Scheduler.actOnChangedTaskType = function () {
  Scheduler.toggleFieldsByTaskType($(this).val());
};

Scheduler.actOnChangeSchedulerTableGarbageCollectionAllTables = (theCheckbox) => {
  const $numberOfDays = $('#task_tableGarbageCollection_numberOfDays');
  const $taskTableGarbageCollectionTable = $('#task_tableGarbageCollection_table');

  if (theCheckbox.prop('checked')) {
    $taskTableGarbageCollectionTable.prop('disabled', true);
    $numberOfDays.prop('disabled', true);
  } else {
    let numberOfDays = parseInt($numberOfDays.val());
    if (numberOfDays < 1) {
      const selectedTable = $taskTableGarbageCollectionTable.val();
      if (typeof defaultNumberOfDays[selectedTable] !== 'undefined') {
        numberOfDays = defaultNumberOfDays[selectedTable];
      }
    }

    $taskTableGarbageCollectionTable.prop('disabled', false);
    if (numberOfDays > 0) {
      $numberOfDays.prop('disabled', false);
    }
  }
};

Scheduler.actOnChangeSchedulerTableGarbageCollectionTable = (theSelector) => {
  const $numberOfDays = $('#task_tableGarbageCollection_numberOfDays');
  const selectedValue = theSelector.val();

  if (defaultNumberOfDays[selectedValue] > 0) {
    $numberOfDays.prop('disabled', false);
    $numberOfDays.val(defaultNumberOfDays[selectedValue]);
  } else {
    $numberOfDays.prop('disabled', true);
    $numberOfDays.val(0);
  }
};

Scheduler.checkOrUncheckAllCheckboxes = (theSelector) => {
  theSelector
    .parents('.tx_scheduler_mod1_table')
    .find(':checkbox')
    .prop('checked', !allCheckedStatus);

  allCheckedStatus = !allCheckedStatus;
  return false;
};

Scheduler.toggleFieldsByTaskType = (taskType) => {
  taskType = parseInt(taskType);
  $('#task_end_col').toggle(taskType === 2);
  $('#task_frequency_row').toggle(taskType === 2);
};

Scheduler.toggleTaskGroups = (theSelector) => {
  const taskGroup = theSelector.data('task-group-id');
  $(`#recordlist-task-group-${taskGroup}`).collapse('toggle');
};

Scheduler.initializeEvents = () => {
  $('.checkall').on('click', function () {
    Scheduler.checkOrUncheckAllCheckboxes($(this));
  });

  $('#task_class').on('change', function () {
    Scheduler.actOnChangedTaskClass($(this));
  });

  $('#task_type').on('change', Scheduler.actOnChangedTaskType);

  $('#task_tableGarbageCollection_allTables').on('change', function () {
    Scheduler.actOnChangeSchedulerTableGarbageCollectionAllTables($(this));
  });

  $('#task_tableGarbageCollection_table').on('change', function () {
    Scheduler.actOnChangeSchedulerTableGarbageCollectionTable($(this));
  });

  $('.taskGroup').on('click', function () {
    Scheduler.toggleTaskGroups($(this));
  });

  new Tablesort($('table.taskGroup-table').get(0));
};

Scheduler.initializeDefaultStates = () => {
  const $taskType = $('#task_type');
  if ($taskType.length) {
    Scheduler.toggleFieldsByTaskType($taskType.val());
  }

  const $taskClass = $('#task_class');
  if ($taskClass.length) {
    Scheduler.actOnChangedTaskClass($taskClass);
  }
};

$(() => {
  Scheduler.initializeEvents();
  Scheduler.initializeDefaultStates();
});

export default Scheduler;
