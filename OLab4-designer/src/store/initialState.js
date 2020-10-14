// @flow
import { initialMapState } from '../redux/map/reducer';
import { initialUserState } from '../redux/login/reducer';
import { initialModalsState } from '../redux/modals/reducer';
import { initialDefaultsState } from '../redux/defaults/reducer';
import { initialTemplatesState } from '../redux/templates/reducer';
import { initialConstructorState } from '../redux/constructor/reducer';
import { initialMapDetailsState } from '../redux/mapDetails/reducer';
import { initialScopeLevelsState } from '../redux/scopeLevels/reducer';
import { initialScopedObjectsState } from '../redux/scopedObjects/reducer';

import type { Map as MapType } from '../redux/map/types';
import type { User as UserType } from '../components/Login/types';
import type { Modals as ModalsType } from '../components/Modals/types';
import type { MapDetails as MapDetailsType } from '../redux/mapDetails/types';
import type { Defaults as DefaultsType } from '../redux/defaults/types';
import type { Templates as TemplatesType } from '../redux/templates/types';
import type { Constructor as ConstructorType } from '../components/Constructor/types';
import type { ScopeLevels as ScopeLevelsType } from '../redux/scopeLevels/types';
import type { ScopedObjectsState as ScopedObjectsType } from '../redux/scopedObjects/types';

export type Store = {
  user: UserType,
  constructor: ConstructorType,
  map: MapType,
  mapDetails: MapDetailsType,
  templates: TemplatesType,
  scopedObjects: ScopedObjectsType,
  modals: ModalsType,
  defaults: DefaultsType,
  scopeLevels: ScopeLevelsType,
};

const initialState: Store = {
  user: initialUserState,
  constructor: initialConstructorState,
  map: initialMapState,
  mapDetails: initialMapDetailsState,
  templates: initialTemplatesState,
  scopedObjects: initialScopedObjectsState,
  modals: initialModalsState,
  defaults: initialDefaultsState,
  scopeLevels: initialScopeLevelsState,
};

export default initialState;
