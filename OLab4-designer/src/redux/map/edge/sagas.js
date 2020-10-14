import {
  call, put, select, takeEvery,
} from 'redux-saga/effects';

import { createEdge, deleteEdge, updateEdge } from '../../../services/api/edge';

import { CREATE_EDGE, DELETE_EDGE, UPDATE_EDGE } from '../types';

import { ACTION_EXCHANGE_EDGE_ID } from '../action';
import { ACTION_NOTIFICATION_ERROR, ACTION_NOTIFICATION_SUCCESS } from '../../notifications/action';

import { MESSAGES } from '../../notifications/config';

function* createEdgeSaga({ edge, edge: { id: prevEdgeId } }) {
  try {
    const mapId = yield select(({ mapDetails }) => mapDetails.id);
    const newEdgeId = yield call(createEdge, mapId, edge);

    yield put(ACTION_EXCHANGE_EDGE_ID(prevEdgeId, newEdgeId));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* deleteEdgeSaga({ edgeId, nodeId }) {
  try {
    const mapId = yield select(({ mapDetails }) => mapDetails.id);

    yield call(deleteEdge, mapId, edgeId, nodeId);
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* updateEdgeSaga({ edge }) {
  try {
    const mapId = yield select(({ mapDetails }) => mapDetails.id);

    yield call(updateEdge, mapId, edge);
    yield put(ACTION_NOTIFICATION_SUCCESS(MESSAGES.ON_UPDATE.EDGE));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* edgeSaga() {
  yield takeEvery(CREATE_EDGE, createEdgeSaga);
  yield takeEvery(DELETE_EDGE, deleteEdgeSaga);
  yield takeEvery(UPDATE_EDGE, updateEdgeSaga);
}

export default edgeSaga;
