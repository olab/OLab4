// @flow
import type {
  ScopedObjectDetails as ScopedObjectDetailsType,
} from '../../../../redux/scopedObjects/types';

export type IEyeComponentProps = {
  classes: {
    [props: string]: any,
  },
  additionalInfo: null | ScopedObjectDetailsType,
  scopedObjectId: number,
  scopedObjectType: string,
  isShowSpinner: boolean,
  ACTION_SCOPED_OBJECT_DETAILS_REQUESTED: Function,
};

export type IEyeComponentState = {
  isShowTooltip: boolean,
  eyeIconRef: any,
};
