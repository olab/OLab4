import { call, put, takeLatest } from 'redux-saga/effects';
import { getCounterGrid, updateCounterGrid } from '../../services/api/counterGrid';

import { GET_COUNTER_GRID_REQUESTED, UPDATE_COUNTER_GRID_REQUESTED } from './types';

import { ACTION_NOTIFICATION_ERROR, ACTION_NOTIFICATION_SUCCESS } from '../notifications/action';

import {
  ACTION_GET_COUNTER_GRID_FAILED,
  ACTION_GET_COUNTER_GRID_SUCCEEDED,
  ACTION_UPDATE_COUNTER_GRID_FAILED,
  ACTION_UPDATE_COUNTER_GRID_SUCCEEDED,
} from './action';

import { MESSAGES } from '../notifications/config';

function* getCounterGridSaga({ mapId }) {
  try {
    const counterGrid = yield call(getCounterGrid, mapId);

    yield put(ACTION_GET_COUNTER_GRID_SUCCEEDED(counterGrid));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_GET_COUNTER_GRID_FAILED());
    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* updateCounterGridSaga({ mapId, counterActions, counterValues }) {
  try {
    yield call(updateCounterGrid, mapId, counterActions);

    yield put(ACTION_UPDATE_COUNTER_GRID_SUCCEEDED(counterValues));
    yield put(ACTION_NOTIFICATION_SUCCESS(MESSAGES.ON_UPDATE.COUNTER_ACTIONS));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_UPDATE_COUNTER_GRID_FAILED());
    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* counterGridSaga() {
  yield takeLatest(GET_COUNTER_GRID_REQUESTED, getCounterGridSaga);
  yield takeLatest(UPDATE_COUNTER_GRID_REQUESTED, updateCounterGridSaga);
}

export default counterGridSaga;
