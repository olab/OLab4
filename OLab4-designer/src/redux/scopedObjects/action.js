// @flow
import store from '../../store/store';

import {
  type ScopedObject as ScopedObjectType,
  type ScopedObjects as ScopedObjectsType,
  type ScopedObjectBase as ScopedObjectBaseType,
  type ScopedObjectListItem as ScopedObjectListItemType,
  SCOPED_OBJECTS_REQUESTED,
  SCOPED_OBJECTS_REQUEST_FAILED,
  SCOPED_OBJECTS_REQUEST_SUCCEEDED,
  SCOPED_OBJECTS_TYPED_REQUESTED,
  SCOPED_OBJECTS_TYPED_SUCCEEDED,
  SCOPED_OBJECTS_TYPED_FAILED,
  SCOPED_OBJECTS_CLEAR,
  SCOPED_OBJECT_DETAILS_REQUESTED,
  SCOPED_OBJECT_DETAILS_FULFILLED,
  SCOPED_OBJECT_CREATE_REQUESTED,
  SCOPED_OBJECT_CREATE_SUCCEEDED,
  SCOPED_OBJECT_CREATE_FAILED,
  SCOPED_OBJECT_UPDATE_REQUESTED,
  SCOPED_OBJECT_UPDATE_FULFILLED,
  SCOPED_OBJECT_DELETE_REQUESTED,
  SCOPED_OBJECT_DELETE_SUCCEEDED,
  SCOPED_OBJECT_DELETE_FAILED,
} from './types';

export const ACTION_SCOPED_OBJECTS_REQUEST_SUCCEEDED = (scopedObjectsData: ScopedObjectsType) => ({
  type: SCOPED_OBJECTS_REQUEST_SUCCEEDED,
  scopedObjectsData,
});

export const ACTION_SCOPED_OBJECTS_REQUEST_FAILED = () => ({
  type: SCOPED_OBJECTS_REQUEST_FAILED,
});

export const ACTION_SCOPED_OBJECTS_REQUESTED = () => ({
  type: SCOPED_OBJECTS_REQUESTED,
});

export const ACTION_SCOPED_OBJECT_DETAILS_REQUESTED = (
  scopedObjectId: number,
  scopedObjectType: string,
) => {
  const { scopedObjects } = store.getState();
  const scopedObjectsList = scopedObjects[scopedObjectType];
  const scopedObjectIndex = scopedObjectsList.findIndex(({ id }) => id === scopedObjectId);
  const clonedScopedObject = {
    ...scopedObjectsList[scopedObjectIndex],
    isDetailsFetching: true,
  };

  return {
    type: SCOPED_OBJECT_DETAILS_REQUESTED,
    scopedObjectId,
    scopedObjectType,
    scopedObjectIndex,
    scopedObject: clonedScopedObject,
  };
};

export const ACTION_SCOPED_OBJECT_DETAILS_FAILED = (
  scopedObjectId: number,
  scopedObjectType: string,
) => {
  const { scopedObjects } = store.getState();
  const scopedObjectsList = scopedObjects[scopedObjectType];
  const scopedObjectIndex = scopedObjectsList.findIndex(({ id }) => id === scopedObjectId);
  const clonedScopedObject = {
    ...scopedObjectsList[scopedObjectIndex],
    isDetailsFetching: false,
  };

  return {
    type: SCOPED_OBJECT_DETAILS_FULFILLED,
    scopedObjectType,
    scopedObjectIndex,
    scopedObject: clonedScopedObject,
  };
};

export const ACTION_SCOPED_OBJECT_DETAILS_SUCCEEDED = (
  scopedObjectId: number,
  scopedObjectType: string,
  scopedObjectDetails: ScopedObjectType,
) => {
  const { scopedObjects } = store.getState();
  const scopedObjectsList = scopedObjects[scopedObjectType];
  const scopedObjectIndex = scopedObjectsList.findIndex(({ id }) => id === scopedObjectId);
  const clonedScopedObject = {
    ...scopedObjectsList[scopedObjectIndex],
    ...scopedObjectDetails,
    isDetailsFetching: false,
  };

  return {
    type: SCOPED_OBJECT_DETAILS_FULFILLED,
    scopedObjectType,
    scopedObjectIndex,
    scopedObject: clonedScopedObject,
  };
};

export const ACTION_SCOPED_OBJECT_CREATE_REQUESTED = (
  scopedObjectType: string,
  scopedObjectData: ScopedObjectBaseType,
) => ({
  type: SCOPED_OBJECT_CREATE_REQUESTED,
  scopedObjectType,
  scopedObjectData,
});

export const ACTION_SCOPED_OBJECT_CREATE_SUCCEEDED = (
  scopedObjectId: number,
  scopedObjectType: string,
  scopedObjectData: ScopedObjectType,
) => ({
  type: SCOPED_OBJECT_CREATE_SUCCEEDED,
  scopedObjectId,
  scopedObjectType,
  scopedObjectData,
});

export const ACTION_SCOPED_OBJECT_CREATE_FAILED = () => ({
  type: SCOPED_OBJECT_CREATE_FAILED,
});

export const ACTION_SCOPED_OBJECT_UPDATE_REQUESTED = (
  scopedObjectId: number,
  scopedObjectType: string,
  scopedObjectData: ScopedObjectBaseType,
) => ({
  type: SCOPED_OBJECT_UPDATE_REQUESTED,
  scopedObjectId,
  scopedObjectType,
  scopedObjectData,
});

export const ACTION_SCOPED_OBJECT_UPDATE_FULFILLED = () => ({
  type: SCOPED_OBJECT_UPDATE_FULFILLED,
});

export const ACTION_SCOPED_OBJECTS_TYPED_REQUESTED = (scopedObjectType: string) => ({
  type: SCOPED_OBJECTS_TYPED_REQUESTED,
  scopedObjectType,
});

export const ACTION_SCOPED_OBJECTS_TYPED_SUCCEEDED = (
  scopedObjectType: string,
  scopedObjects: Array<ScopedObjectListItemType>,
) => ({
  type: SCOPED_OBJECTS_TYPED_SUCCEEDED,
  scopedObjectType,
  scopedObjects,
});

export const ACTION_SCOPED_OBJECTS_TYPED_FAILED = () => ({
  type: SCOPED_OBJECTS_TYPED_FAILED,
});

export const ACTION_SCOPED_OBJECTS_CLEAR = () => ({
  type: SCOPED_OBJECTS_CLEAR,
});

export const ACTION_SCOPED_OBJECT_DELETE_REQUESTED = (
  scopedObjectId: number,
  scopedObjectType: string,
) => ({
  type: SCOPED_OBJECT_DELETE_REQUESTED,
  scopedObjectId,
  scopedObjectType,
});

export const ACTION_SCOPED_OBJECT_DELETE_SUCCEEDED = (
  scopedObjectId: number,
  scopedObjectType: string,
) => {
  const { scopedObjects } = store.getState();
  const scopedObjectsList = scopedObjects[scopedObjectType];
  const scopedObjectIndex = scopedObjectsList.findIndex(({ id }) => id === scopedObjectId);

  return {
    type: SCOPED_OBJECT_DELETE_SUCCEEDED,
    scopedObjectIndex,
    scopedObjectType,
  };
};

export const ACTION_SCOPED_OBJECT_DELETE_FAILED = () => ({
  type: SCOPED_OBJECT_DELETE_FAILED,
});
