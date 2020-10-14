import styled from 'styled-components';
import { DARK_BLUE, LIGHT_WHITE_BLUE } from '../../colors';

export const Wrapper = styled.form`
  position: absolute;
  left: 50%;
  top: 0;
  width: 300px;
  padding: 3px 13px 14px 0;
  z-index: 1000;
  box-sizing: border-box;
  border-radius: 5px;
  border: 2px solid ${DARK_BLUE};
  background: ${LIGHT_WHITE_BLUE};
`;

export const Container = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
`;

const styles = () => ({
  input: {
    width: '225px',
    transform: 'scale(0.8)',
  },
  iconButton: {
    margin: '12px 0 0',
  },
});

export default styles;
