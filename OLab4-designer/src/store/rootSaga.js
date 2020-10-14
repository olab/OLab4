// @flow
import { all } from 'redux-saga/effects';

import mapSaga from '../redux/map/sagas';
import nodeGridSaga from '../redux/nodeGrid/sagas';
import defaultsSaga from '../redux/defaults/sagas';
import templatesSaga from '../redux/templates/sagas';
import authUserSaga from '../redux/login/sagas';
import mapDetailsSaga from '../redux/mapDetails/sagas';
import scopeLevelsSaga from '../redux/scopeLevels/sagas';
import counterGridSaga from '../redux/counterGrid/sagas';
import scopedObjectsSaga from '../redux/scopedObjects/sagas';
import edgeSaga from '../redux/map/edge/sagas';
import nodeSaga from '../redux/map/node/sagas';

export default function* rootSaga(): Generator<any, void, void> {
  yield all([
    mapSaga(),
    edgeSaga(),
    nodeSaga(),
    nodeGridSaga(),
    defaultsSaga(),
    authUserSaga(),
    templatesSaga(),
    mapDetailsSaga(),
    scopeLevelsSaga(),
    counterGridSaga(),
    scopedObjectsSaga(),
  ]);
}
