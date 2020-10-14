// @flow
import type { MapDetails } from '../../../redux/mapDetails/types';

export type BasicDetailsProps = {
  classes: {
    [prop: string]: any,
  },
  details: MapDetails,
  handleInputChange: Function,
  handleEditorChange: Function,
};
