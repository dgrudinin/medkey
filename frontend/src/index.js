import React from 'react';
import { render } from 'react-dom';
import { Provider } from 'react-redux';
import thunk from 'redux-thunk';
import { createStore, applyMiddleware } from 'redux';
import '../modules/medical/components/index.css';
import { reducerApp, initWorkplaceState } from '../modules/medical/reducers/reducers';
import { changeDate } from '../modules/medical/actions/actions';
import ScheduleContainer from '../modules/medical/containers/ScheduleContainer';
import PatientContainer from '../modules/medical/containers/PatientContainer';

if (document.getElementById('app-workplace')) {
    // todo придумать помодульную декомпозицию
    let store = createStore(reducerApp, initWorkplaceState, applyMiddleware(thunk));
    store.dispatch(changeDate());
    render(
        <Provider store={store}>
            <div className="b-workplace">
                <PatientContainer/>
                <ScheduleContainer/>
            </div>
        </Provider>,
        document.getElementById('app-workplace')
    );
}

// bower/npm
require('../../vendor/bower-asset/jquery-ui/themes/smoothness/jquery-ui.css');
require('../../vendor/bower-asset/bootstrap/dist/css/bootstrap.css');

// kartik
require('../../vendor/kartik-v/yii2-widget-datetimepicker/assets/css/bootstrap-datetimepicker.css');
require('../../vendor/kartik-v/yii2-widget-datetimepicker/assets/css/datetimepicker-kv.css');
require('../../vendor/kartik-v/yii2-widget-datepicker/assets/css/bootstrap-datepicker3.css');
require('../../vendor/kartik-v/yii2-widget-datepicker/assets/css/datepicker-kv.css');
require('../../vendor/kartik-v/yii2-widget-select2/assets/css/select2.css');
require('../../vendor/kartik-v/yii2-widget-select2/assets/css/select2-addl.css');
require('../../vendor/kartik-v/yii2-widget-select2/assets/css/select2-krajee.css');
require('../../vendor/kartik-v/yii2-widget-timepicker/assets/css/bootstrap-timepicker.min.css');

// app
require('../../resources/css/core/main.css');
require('../../modules/config/resources/css/directory.css');
require('../../modules/crm/resources/css/order.css');
require('../../modules/medical/resources/css/medical.css');
require('../../modules/organization/resources/css/organization.css');
require('../../modules/security/resources/css/security.css');
require('../../modules/workplan/resources/css/workplan.css');