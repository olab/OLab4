import {
  call, put, select, takeLatest,
} from 'redux-saga/effects';
import { getMapDetails, updateMapDetails } from '../../services/api/mapDetails';

import { ACTION_GET_MAP_DETAILS_FAILED, ACTION_GET_MAP_DETAILS_SUCCEEDED } from './action';
import { ACTION_NOTIFICATION_ERROR, ACTION_NOTIFICATION_SUCCESS } from '../notifications/action';

import { MESSAGES } from '../notifications/config';

import { GET_MAP_DETAILS_REQUESTED, UPDATE_MAP_DETAILS_REQUESTED } from './types';

function* getMapDetailsSaga({ mapId }) {
  try {
    const mapDetails = yield call(getMapDetails, mapId);

    yield put(ACTION_GET_MAP_DETAILS_SUCCEEDED(mapDetails));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_GET_MAP_DETAILS_FAILED());
    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* updateMapDetailsSaga({ mapDetails: newMapDetails }) {
  try {
    const oldMapDetails = yield select(({ mapDetails }) => mapDetails);
    const updatedMapDetails = { ...oldMapDetails, ...newMapDetails };

    yield call(updateMapDetails, updatedMapDetails);

    yield put(ACTION_NOTIFICATION_SUCCESS(MESSAGES.ON_UPDATE.MAP_DETAILS));
  } catch (error) {
    const { response, message } = error;
    const errorMessage = response ? response.statusText : message;

    yield put(ACTION_NOTIFICATION_ERROR(errorMessage));
  }
}

function* mapDetailsSaga() {
  yield takeLatest(GET_MAP_DETAILS_REQUESTED, getMapDetailsSaga);
  yield takeLatest(UPDATE_MAP_DETAILS_REQUESTED, updateMapDetailsSaga);
}

export default mapDetailsSaga;
