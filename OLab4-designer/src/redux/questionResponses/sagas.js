import {
  call, put, takeLatest, takeEvery,
} from 'redux-saga/effects';

import {
  getResponseDetails,
  createResponse,
  editResponse,
  deleteResponse,
} from '../../services/api/questionResponses';

import {
  SCOPED_OBJECT_DETAILS_REQUESTED,
  SCOPED_OBJECT_CREATE_REQUESTED,
  SCOPED_OBJECT_UPDATE_REQUESTED,
  SCOPED_OBJECT_DELETE_REQUESTED,
} from './types';

import {
  ACTION_SCOPED_OBJECT_DETAILS_FAILED,
  ACTION_SCOPED_OBJECT_DETAILS_SUCCEEDED,
  ACTION_SCOPED_OBJECT_CREATE_SUCCEEDED,
  ACTION_SCOPED_OBJECT_CREATE_FAILED,
  ACTION_SCOPED_OBJECT_UPDATE_FULFILLED,
  ACTION_SCOPED_OBJECT_DELETE_SUCCEEDED,
  ACTION_SCOPED_OBJECT_DELETE_FAILED,
} from './action';

import {
  ACTION_NOTIFICATION_ERROR,
  ACTION_NOTIFICATION_SUCCESS,
} from '../notifications/action';

import {
  MESSAGES,
} from '../notifications/config';

// import { SCOPED_OBJECTS } from '../../components/config';

function* createResponseSaga({ questionId, scopedObjectData }) {
  try {
    const scopedObjectId = yield call(
      createResponse,
      questionId,
      scopedObjectData,
    );

    yield put(ACTION_SCOPED_OBJECT_CREATE_SUCCEEDED(
      scopedObjectId,
      scopedObjectData,
    ));
    yield put(ACTION_NOTIFICATION_SUCCESS(MESSAGES.ON_CREATE.SCOPED_OBJECT));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_SCOPED_OBJECT_CREATE_FAILED());
    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

// function* getResponseDetailsSaga({ questionId, questionResponseId }) {
function* getResponseDetailsSaga(arg) {
  const { questionId, scopedObjectId } = arg;
  try {
    const scopedObjectDetails = yield call(
      getResponseDetails,
      questionId,
      scopedObjectId,
    );

    const action = ACTION_SCOPED_OBJECT_DETAILS_SUCCEEDED(
      scopedObjectId,
      'questionresponses',
      scopedObjectDetails,
    );
    yield put(action);
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_SCOPED_OBJECT_DETAILS_FAILED(scopedObjectId));
    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* deleteResponseSaga({
  questionId,
  scopedObjectId,
}) {
  try {
    yield call(
      deleteResponse,
      questionId,
      scopedObjectId,
    );

    yield put(ACTION_SCOPED_OBJECT_DELETE_SUCCEEDED(scopedObjectId));
    yield put(ACTION_NOTIFICATION_SUCCESS(MESSAGES.ON_DELETE.SCOPED_OBJECT));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_SCOPED_OBJECT_DELETE_FAILED());
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

  yield put(ACTION_SCOPED_OBJECT_UPDATE_FULFILLED());
}

function* questionResponseSaga() {
  yield takeLatest(SCOPED_OBJECT_CREATE_REQUESTED, createResponseSaga);
  yield takeLatest(SCOPED_OBJECT_UPDATE_REQUESTED, updateResponseSaga);
  yield takeLatest(SCOPED_OBJECT_DELETE_REQUESTED, deleteResponseSaga);
  yield takeEvery(SCOPED_OBJECT_DETAILS_REQUESTED, getResponseDetailsSaga);
}

export default questionResponseSaga;
