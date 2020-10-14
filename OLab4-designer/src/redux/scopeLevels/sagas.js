import { call, put, takeLatest } from 'redux-saga/effects';
import { getScopeLevels } from '../../services/api/scopeLevels';

import { SCOPE_LEVELS_REQUESTED } from './types';

import { ACTION_NOTIFICATION_ERROR } from '../notifications/action';
import { ACTION_SCOPE_LEVELS_REQUEST_FAILED, ACTION_SCOPE_LEVELS_REQUEST_SUCCEEDED } from './action';

function* getScopeLevelsSaga({ level }) {
  try {
    const scopeLevels = yield call(getScopeLevels, level);

    yield put(ACTION_SCOPE_LEVELS_REQUEST_SUCCEEDED(level, scopeLevels));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_SCOPE_LEVELS_REQUEST_FAILED());
    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* scopeLevelsSaga() {
  yield takeLatest(SCOPE_LEVELS_REQUESTED, getScopeLevelsSaga);
}

export default scopeLevelsSaga;
