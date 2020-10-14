import {
  call, put, select, takeEvery,
} from 'redux-saga/effects';

import {
  createNode, deleteNode, updateNode, getNode,
} from '../../../services/api/node';
import generateTmpId from '../../../helpers/generateTmpId';

import {
  CREATE_NODE,
  UPDATE_NODE,
  GET_NODE_REQUESTED,
  DELETE_NODE_REQUESTED,
  CREATE_NODE_WITH_EDGE,
} from '../types';

import {
  ACTION_EXCHANGE_NODE_ID,
  ACTION_EXCHANGE_EDGE_ID,
  ACTION_GET_NODE_FULLFILLED,
  ACTION_DELETE_NODE_FULLFILLED,
} from '../action';
import {
  ACTION_NOTIFICATION_ERROR,
  ACTION_NOTIFICATION_SUCCESS,
} from '../../notifications/action';

import { MESSAGES, ERROR_MESSAGES } from '../../notifications/config';

function* getNodeSaga({ mapId, nodeId }) {
  try {
    const node = yield call(getNode, mapId, nodeId);

    yield put(ACTION_GET_NODE_FULLFILLED(node));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* createNodeSaga({ node: { id: oldNodeId, x, y } }) {
  try {
    const mapId = yield select(({ mapDetails }) => mapDetails.id);
    const newNodeId = yield call(createNode, mapId, { x, y });

    yield put(ACTION_EXCHANGE_NODE_ID(oldNodeId, newNodeId));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* createNodeWithEdgeSaga({
  sourceNodeId,
  node: { x, y, id: oldNodeId },
  edge: { id: oldEdgeId },
}) {
  try {
    const mapId = yield select(({ mapDetails }) => mapDetails.id);
    const { newNodeId, newEdgeId } = yield call(createNode, mapId, { x, y }, sourceNodeId);

    yield put(ACTION_EXCHANGE_NODE_ID(oldNodeId, newNodeId));
    yield put(ACTION_EXCHANGE_EDGE_ID(oldEdgeId, newEdgeId));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* updateNodeSaga({
  node, isShowNotification, mapIdFromURL, type: actionType,
}) {
  try {
    const mapIdFromStore = yield select(({ mapDetails }) => mapDetails.id);
    const mapId = mapIdFromURL || mapIdFromStore;

    yield call(updateNode, mapId, node);

    const editorPayload = {
      id: generateTmpId(),
      nodeId: node.id,
      mapId,
      actionType,
    };
    const editorPayloadString = JSON.stringify(editorPayload);
    localStorage.setItem('node', editorPayloadString);

    if (isShowNotification) {
      yield put(ACTION_NOTIFICATION_SUCCESS(MESSAGES.ON_UPDATE.NODE));
    }
  } catch (error) {
    const { response, message, name } = error;
    const errorMessage = response ? response.statusText : message;

    if (name === 'QuotaExceededError') {
      yield put(ACTION_NOTIFICATION_ERROR(ERROR_MESSAGES.LOCAL_STORAGE.FULL_MEMORY));

      return;
    }

    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* deleteNodeSaga({ nodeId, mapId: mapIdFromURL, type: actionType }) {
  try {
    const mapIdFromStore = yield select(({ mapDetails }) => mapDetails.id);
    const mapId = mapIdFromStore || mapIdFromURL;
    yield call(deleteNode, mapId, nodeId);

    const editorPayload = {
      id: generateTmpId(),
      mapId,
      nodeId,
      actionType,
    };

    const editorPayloadString = JSON.stringify(editorPayload);
    localStorage.setItem('node', editorPayloadString);
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
  yield put(ACTION_DELETE_NODE_FULLFILLED());
}

function* nodeSaga() {
  yield takeEvery(GET_NODE_REQUESTED, getNodeSaga);
  yield takeEvery(CREATE_NODE, createNodeSaga);
  yield takeEvery(UPDATE_NODE, updateNodeSaga);
  yield takeEvery(DELETE_NODE_REQUESTED, deleteNodeSaga);
  yield takeEvery(CREATE_NODE_WITH_EDGE, createNodeWithEdgeSaga);
}

export default nodeSaga;
