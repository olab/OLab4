// @flow
import type { MapDetails } from '../../../redux/mapDetails/types';

export type AppearanceProps = {
  details: MapDetails,
  themes: Array<string>,
  handleSelectChange: Function,
};
