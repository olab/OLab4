import {
  call, put, select, takeEvery,
} from 'redux-saga/effects';

import {
  createQuestionResponse,
  deleteQuestionResponse,
  getQuestionResponse,
  updateQuestionResponse,
} from '../../services/api/questionResponses';

import {
  CREATE_QUESTION_RESPONSE,
  DELETE_QUESTION_RESPONSE_REQUESTED,
  GET_QUESTION_RESPONSE_REQUESTED,
  UPDATE_QUESTION_RESPONSE,
} from './types';

import {
  ACTION_EXCHANGE_QUESTION_RESPONSE_ID,
  ACTION_GET_QUESTION_RESPONSE_FULLFILLED,
  ACTION_DELETE_QUESTION_RESPONSE_FULLFILLED,
} from './action';

import {
  ACTION_NOTIFICATION_ERROR,
  ACTION_NOTIFICATION_SUCCESS,
} from '../notifications/action';

import {
  MESSAGES,
  ERROR_MESSAGES,
} from '../notifications/config';

function* getQuestionResponseSaga({ questionId, questionResponseId }) {
  try {
    const node = yield call(getQuestionResponse, questionId, questionResponseId);

    yield put(ACTION_GET_QUESTION_RESPONSE_FULLFILLED(node));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* createQuestionResponseSaga({ node: { id: oldQuestionResponseId, x, y } }) {
  try {
    const questionId = yield select(({ mapDetails }) => mapDetails.id);
    const newQuestionResponseId = yield call(createQuestionResponse, questionId, { x, y });

    yield put(ACTION_EXCHANGE_QUESTION_RESPONSE_ID(oldQuestionResponseId, newQuestionResponseId));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* createQuestionResponseWithEdgeSaga({
  sourceQuestionResponseId,
  node: { x, y, id: oldQuestionResponseId },
  edge: { id: oldEdgeId },
}) {
  try {
    const questionId = yield select(({ mapDetails }) => mapDetails.id);
    const { newQuestionResponseId, newEdgeId } = yield call(createQuestionResponse, questionId, { x, y }, sourceQuestionResponseId);

    yield put(ACTION_EXCHANGE_QUESTION_RESPONSE_ID(oldQuestionResponseId, newQuestionResponseId));
    yield put(ACTION_EXCHANGE_EDGE_ID(oldEdgeId, newEdgeId));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* updateQuestionResponseSaga({
  node, isShowNotification, questionIdFromURL, type: actionType,
}) {
  try {
    const questionIdFromStore = yield select(({ mapDetails }) => mapDetails.id);
    const questionId = questionIdFromURL || questionIdFromStore;

    yield call(updateQuestionResponse, questionId, node);

    const editorPayload = {
      id: generateTmpId(),
      questionResponseId: node.id,
      questionId,
      actionType,
    };
    const editorPayloadString = JSON.stringify(editorPayload);
    localStorage.setItem('node', editorPayloadString);

    if (isShowNotification) {
      yield put(ACTION_NOTIFICATION_SUCCESS(MESSAGES.ON_UPDATE.QUESTION_RESPONSE));
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

function* deleteQuestionResponseSaga({ questionResponseId, questionId: questionIdFromURL, type: actionType }) {
  try {
    const questionIdFromStore = yield select(({ mapDetails }) => mapDetails.id);
    const questionId = questionIdFromStore || questionIdFromURL;
    yield call(deleteQuestionResponse, questionId, questionResponseId);

    const editorPayload = {
      id: generateTmpId(),
      questionId,
      questionResponseId,
      actionType,
    };

    const editorPayloadString = JSON.stringify(editorPayload);
    localStorage.setItem('node', editorPayloadString);
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
  yield put(ACTION_DELETE_QUESTION_RESPONSE_FULLFILLED());
}

function* nodeSaga() {
  yield takeEvery(GET_QUESTION_RESPONSE_REQUESTED, getQuestionResponseSaga);
  yield takeEvery(CREATE_QUESTION_RESPONSE, createQuestionResponseSaga);
  yield takeEvery(UPDATE_QUESTION_RESPONSE, updateQuestionResponseSaga);
  yield takeEvery(DELETE_QUESTION_RESPONSE_REQUESTED, deleteQuestionResponseSaga);
  yield takeEvery(CREATE_QUESTION_RESPONSE_WITH_EDGE, createQuestionResponseWithEdgeSaga);
}

export default nodeSaga;