import { call, put, takeLatest } from 'redux-saga/effects';
import { loginUser } from '../../services/api/auth';
import { ACTION_NOTIFICATION_ERROR } from '../notifications/action';

import { USER_AUTH_REQUESTED } from './types';
import { ACTION_USER_AUTH_SUCCEEDED, ACTION_USER_AUTH_FAILED } from './action';

function* loginUserSaga({ userLoginData }) {
  try {
    const { token } = yield call(loginUser, userLoginData);
    localStorage.setItem('token', token);

    yield put(ACTION_USER_AUTH_SUCCEEDED(token));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_USER_AUTH_FAILED());
    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* authUserSaga() {
  yield takeLatest(USER_AUTH_REQUESTED, loginUserSaga);
}

export default authUserSaga;
