import {
  call, put, takeLatest,
} from 'redux-saga/effects';

import {
  createResponse,
  editResponse,
  deleteResponse,
} from '../../services/api/questionResponses';

import {
  RESPONSE_CREATE_REQUESTED,
  RESPONSE_UPDATE_REQUESTED,
  RESPONSE_DELETE_REQUESTED,
} from './types';

import {
  ACTION_RESPONSE_CREATE_SUCCEEDED,
  ACTION_RESPONSE_CREATE_FAILED,
  ACTION_RESPONSE_UPDATE_FULFILLED,
  ACTION_RESPONSE_DELETE_SUCCEEDED,
  ACTION_RESPONSE_DELETE_FAILED,
} from './action';

import {
  ACTION_NOTIFICATION_ERROR,
  ACTION_NOTIFICATION_SUCCESS,
} from '../notifications/action';

import {
  MESSAGES,
} from '../notifications/config';

// import { SCOPED_OBJECTS } from '../../components/config';

function* createResponseSaga({ scopedObjectData }) {
  try {
    const scopedObjectId = yield call(
      createResponse,
      scopedObjectData,
    );

    yield put(ACTION_RESPONSE_CREATE_SUCCEEDED(
      scopedObjectId,
      scopedObjectData,
    ));
    yield put(ACTION_NOTIFICATION_SUCCESS(MESSAGES.ON_CREATE.SCOPED_OBJECT));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_RESPONSE_CREATE_FAILED());
    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* deleteResponseSaga({
  scopedObjectId,
}) {
  try {
    yield call(
      deleteResponse,
      scopedObjectId,
    );

    yield put(ACTION_RESPONSE_DELETE_SUCCEEDED(scopedObjectId, 'questionresponses'));
    yield put(ACTION_NOTIFICATION_SUCCESS(MESSAGES.ON_DELETE.SCOPED_OBJECT));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_RESPONSE_DELETE_FAILED());
    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* updateResponseSaga({
  scopedObjectData,
}) {
  try {
    yield call(
      editResponse,
      scopedObjectData,
    );

    yield put(ACTION_NOTIFICATION_SUCCESS(MESSAGES.ON_UPDATE.SCOPED_OBJECT));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }

  yield put(ACTION_RESPONSE_UPDATE_FULFILLED());
}

function* questionResponseSaga() {
  yield takeLatest(RESPONSE_CREATE_REQUESTED, createResponseSaga);
  yield takeLatest(RESPONSE_UPDATE_REQUESTED, updateResponseSaga);
  yield takeLatest(RESPONSE_DELETE_REQUESTED, deleteResponseSaga);
}

export default questionResponseSaga;
