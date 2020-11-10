import {
  call, put, takeLatest, takeEvery,
} from 'redux-saga/effects';
import {
  getScopedObjectDetails,
  createScopedObject,
  editScopedObject,
  deleteScopedObject,
} from '../../services/api/scopedObjects';

import {
  SCOPED_OBJECT_DETAILS_REQUESTED,
  SCOPED_OBJECT_CREATE_REQUESTED,
  SCOPED_OBJECT_UPDATE_REQUESTED,
  SCOPED_OBJECT_DELETE_REQUESTED,
} from './types';

import { ACTION_NOTIFICATION_ERROR, ACTION_NOTIFICATION_SUCCESS } from '../notifications/action';
import {
  ACTION_SCOPED_OBJECT_DETAILS_FAILED,
  ACTION_SCOPED_OBJECT_DETAILS_SUCCEEDED,
  ACTION_SCOPED_OBJECT_CREATE_SUCCEEDED,
  ACTION_SCOPED_OBJECT_CREATE_FAILED,
  ACTION_SCOPED_OBJECT_UPDATE_FULFILLED,
  ACTION_SCOPED_OBJECT_DELETE_SUCCEEDED,
  ACTION_SCOPED_OBJECT_DELETE_FAILED,
} from './action';

import { MESSAGES } from '../notifications/config';

function* getScopedObjectDetailsSaga({ scopedObjectId, scopedObjectType }) {
  try {
    const scopedObjectDetails = yield call(
      getScopedObjectDetails,
      scopedObjectId,
      scopedObjectType,
    );

    yield put(ACTION_SCOPED_OBJECT_DETAILS_SUCCEEDED(
      scopedObjectId,
      scopedObjectType,
      scopedObjectDetails,
    ));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_SCOPED_OBJECT_DETAILS_FAILED(scopedObjectId, scopedObjectType));
    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* createScopedObjectSaga({ scopedObjectType, scopedObjectData }) {
  try {
    const scopedObjectId = yield call(
      createScopedObject,
      scopedObjectType,
      scopedObjectData,
    );

    yield put(ACTION_SCOPED_OBJECT_CREATE_SUCCEEDED(
      scopedObjectId,
      scopedObjectType,
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

function* updateScopedObjectSaga({ scopedObjectId, scopedObjectType, scopedObjectData }) {
  try {
    yield call(
      editScopedObject,
      scopedObjectId,
      scopedObjectType,
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

function* deleteScopedObjectSaga({ scopedObjectId, scopedObjectType }) {
  try {
    yield call(deleteScopedObject, scopedObjectId, scopedObjectType);

    yield put(ACTION_SCOPED_OBJECT_DELETE_SUCCEEDED(scopedObjectId, scopedObjectType));
    yield put(ACTION_NOTIFICATION_SUCCESS(MESSAGES.ON_DELETE.SCOPED_OBJECT));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_SCOPED_OBJECT_DELETE_FAILED());
    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* scopedObjectsSaga() {
  yield takeLatest(SCOPED_OBJECT_CREATE_REQUESTED, createScopedObjectSaga);
  yield takeLatest(SCOPED_OBJECT_UPDATE_REQUESTED, updateScopedObjectSaga);
  yield takeLatest(SCOPED_OBJECT_DELETE_REQUESTED, deleteScopedObjectSaga);
  yield takeEvery(SCOPED_OBJECT_DETAILS_REQUESTED, getScopedObjectDetailsSaga);
}

export default scopedObjectsSaga;
