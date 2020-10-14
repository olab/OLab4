import {
  call, put, select, takeLatest,
} from 'redux-saga/effects';
import { getTemplates, createTemplate } from '../../services/api/templates';

import { TEMPLATE_UPLOAD_REQUESTED, TEMPLATES_REQUESTED } from './types';
import { ACTION_NOTIFICATION_ERROR, ACTION_NOTIFICATION_SUCCESS } from '../notifications/action';
import {
  ACTION_TEMPLATES_REQUEST_FAILED,
  ACTION_TEMPLATES_REQUEST_SUCCEEDED,
  ACTION_TEMPLATE_UPLOAD_FULFILLED,
} from './action';

import { MESSAGES } from '../notifications/config';

function* createTemplateSaga({ templateName }) {
  try {
    const mapId = yield select(({ mapDetails }) => mapDetails.id);

    yield call(createTemplate, mapId, templateName);
    yield put(ACTION_NOTIFICATION_SUCCESS(MESSAGES.ON_CREATE.TEMPLATE));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }

  yield put(ACTION_TEMPLATE_UPLOAD_FULFILLED());
}

function* getTemplatesSaga() {
  try {
    const oldTemplates = yield select(({ templates }) => templates.list);
    const newTemplates = yield call(getTemplates);

    yield put(ACTION_TEMPLATES_REQUEST_SUCCEEDED(oldTemplates, newTemplates));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_TEMPLATES_REQUEST_FAILED());
    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* templatesSaga() {
  yield takeLatest(TEMPLATE_UPLOAD_REQUESTED, createTemplateSaga);
  yield takeLatest(TEMPLATES_REQUESTED, getTemplatesSaga);
}

export default templatesSaga;
