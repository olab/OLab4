// @flow
import type { MapDetails } from '../../../redux/mapDetails/types';

export type BasicDetailsProps = {
  classes: {
    [prop: string]: any,
  },
  details: MapDetails,
  onInputChange: Function,
  handleEditorChange: Function,
};
