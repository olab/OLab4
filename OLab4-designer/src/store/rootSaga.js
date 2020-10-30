// @flow
import { all } from 'redux-saga/effects';

import authUserSaga from '../redux/login/sagas';
import counterGridSaga from '../redux/counterGrid/sagas';
import defaultsSaga from '../redux/defaults/sagas';
import edgeSaga from '../redux/map/edge/sagas';
import mapDetailsSaga from '../redux/mapDetails/sagas';
import mapSaga from '../redux/map/sagas';
import nodeGridSaga from '../redux/nodeGrid/sagas';
import nodeSaga from '../redux/map/node/sagas';
import questionResponsesSaga from '../redux/questionResponses/sagas';
import scopedObjectsSaga from '../redux/scopedObjects/sagas';
import scopeLevelsSaga from '../redux/scopeLevels/sagas';
import templatesSaga from '../redux/templates/sagas';

export default function* rootSaga(): Generator<any, void, void> {
  yield all([
    authUserSaga(),
    counterGridSaga(),
    defaultsSaga(),
    edgeSaga(),
    mapDetailsSaga(),
    mapSaga(),
    nodeGridSaga(),
    nodeSaga(),
    questionResponsesSaga(),
    scopedObjectsSaga(),
    scopeLevelsSaga(),
    templatesSaga(),
  ]);
}
