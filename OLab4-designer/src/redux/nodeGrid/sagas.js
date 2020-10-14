import {
  put, call, takeLatest, select,
} from 'redux-saga/effects';
import { updateNodeGrid } from '../../services/api/nodeGrid';

import { UPDATE_NODE_GRID_REQUESTED } from './types';

import { ACTION_NOTIFICATION_ERROR, ACTION_NOTIFICATION_SUCCESS } from '../notifications/action';
import { ACTION_UPDATE_NODE_GRID_FAILED, ACTION_UPDATE_NODE_GRID_SUCCEEDED } from './action';

import { MESSAGES } from '../notifications/config';

function* updateNodeGridSaga({ nodes }) {
  try {
    const mapId = yield select(({ mapDetails }) => mapDetails.id);

    yield call(updateNodeGrid, mapId, nodes);

    yield put(ACTION_UPDATE_NODE_GRID_SUCCEEDED(nodes));
    yield put(ACTION_NOTIFICATION_SUCCESS(MESSAGES.ON_UPDATE.NODE_GRID));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_UPDATE_NODE_GRID_FAILED());
    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* nodeGridSaga() {
  yield takeLatest(UPDATE_NODE_GRID_REQUESTED, updateNodeGridSaga);
}

export default nodeGridSaga;
