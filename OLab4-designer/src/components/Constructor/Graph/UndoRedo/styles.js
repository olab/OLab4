import styled from 'styled-components';

import { ContainerWithPseudoBlocks } from '../../Toolbars/styles';

export const Container = styled(ContainerWithPseudoBlocks)`
  & > button {
    margin-right: 10px;
    &:hover:enabled {
      filter: sepia() saturate(10000%) hue-rotate(200deg);
    }

    &:first-child {
      margin-left: 10px;
    }
  }
`;

const styles = () => ({
  undoRedo: {
    padding: '8px',
  },
});

export default styles;
