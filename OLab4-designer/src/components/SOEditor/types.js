// @flow
import type {
  ScopedObject as ScopedObjectType,
  ScopedObjectBase as ScopedObjectBaseType,
} from '../../redux/scopedObjects/types';
import type {
  ScopeLevels as ScopeLevelsType,
} from '../../redux/scopeLevels/types';

export type ISOEditorProps = {
  ACTION_SCOPE_LEVELS_CLEAR: () => void,
  ACTION_SCOPED_OBJECTS_CLEAR: () => void,
};

export type IScopedObjectProps = {
  classes: {
    [props: string]: any,
  },
  match: any,
  history: any,
  scopedObjects: Array<ScopedObjectType>,
  scopeLevels: ScopeLevelsType,
  isScopedObjectCreating: boolean,
  isScopedObjectUpdating: boolean,
  ACTION_SCOPE_LEVELS_REQUESTED: Function,
  ACTION_SCOPED_OBJECT_CREATE_REQUESTED: Function,
  ACTION_SCOPED_OBJECT_DETAILS_REQUESTED: Function,
  ACTION_SCOPED_OBJECT_UPDATE_REQUESTED: Function,
};

export type Icons = {
  iconEven: any,
  iconOdd: any,
};

export type IScopedObjectState = {
  ...ScopedObjectBaseType,
  isShowModal: boolean,
  isFieldsDisabled: boolean,
};
