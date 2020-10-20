// @flow
import type { ScopedObjectListItem as ScopedObjectListItemType } from '../../../redux/scopedObjects/types';

export type ISOListProps = {
  classes: {
    [props: string]: any,
  },
  ACTION_SCOPED_OBJECT_DELETE_REQUESTED: (scopedObjectId: number, scopedObjectType: string) => void,
  ACTION_SCOPED_OBJECTS_TYPED_REQUESTED: (scopedObjectType: string) => void,
  history: any,
  isScopedObjectsFetching: boolean,
  match: any,
  pathName: any,
  scopedObjects: Array<ScopedObjectListItemType>,
};

export type ISOListState = {
  scopedObjectsFiltered: Array<ScopedObjectListItemType>,
};
