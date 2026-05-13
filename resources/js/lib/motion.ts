import type { Transition, Variants } from 'framer-motion';

// FM handles reduced-motion automatically for motion.* components; useReducedMotion()
// is only needed for opt-in effects (count-up animations) that don't route through FM.

const enterEase: Transition = {
    duration: 0.32,
    ease: [0.22, 1, 0.36, 1],
};

export const fadeInUp: Variants = {
    hidden: { opacity: 0, y: 8 },
    visible: { opacity: 1, y: 0, transition: enterEase },
};

export const staggerChildren: Variants = {
    hidden: {},
    visible: {
        transition: { staggerChildren: 0.06, delayChildren: 0.04 },
    },
};

export const staggerItem: Variants = {
    hidden: { opacity: 0, y: 6 },
    visible: { opacity: 1, y: 0, transition: enterEase },
};

export const breath: Variants = {
    idle: {
        scale: [1, 1.02, 1],
        transition: { duration: 2.5, repeat: Infinity, ease: 'easeInOut' },
    },
};

export const idleByMood: Record<string, Variants> = {
    glow: {
        idle: {
            y: [0, -3, 0],
            scale: [1, 1.025, 1],
            transition: { duration: 1.6, repeat: Infinity, ease: 'easeInOut' },
        },
    },
    bouncy: {
        idle: {
            y: [0, -4, 0],
            transition: { duration: 1.2, repeat: Infinity, ease: 'easeOut' },
        },
    },
    dim: {
        idle: {
            rotate: [-2, 2, -2],
            transition: { duration: 4.5, repeat: Infinity, ease: 'easeInOut' },
        },
    },
    spinning: {
        idle: {
            rotate: [0, 360],
            transition: { duration: 12, repeat: Infinity, ease: 'linear' },
        },
    },
    wobble: {
        idle: {
            rotate: [-3, 3, -3, 0],
            transition: { duration: 1.4, repeat: Infinity, repeatDelay: 1.5, ease: 'easeInOut' },
        },
    },
    squished: breath,
};

export const wave: Variants = {
    rest: { rotate: 0 },
    play: {
        rotate: [0, -12, 12, -8, 0],
        transition: { duration: 0.6, ease: 'easeInOut' },
    },
};

export const hop: Variants = {
    rest: { y: 0 },
    play: {
        y: [0, -14, 0],
        transition: { duration: 0.5, ease: 'easeOut' },
    },
};

export const spin: Variants = {
    rest: { rotate: 0 },
    play: {
        rotate: [0, 360],
        transition: { duration: 0.8, ease: 'easeInOut' },
    },
};

export const tapReactions = [wave, hop, spin] as const;

export const accessoryPop: Variants = {
    hidden: { scale: 0, opacity: 0, rotate: -20 },
    visible: {
        scale: 1,
        opacity: 1,
        rotate: 0,
        transition: { duration: 0.45, ease: 'backOut' },
    },
};

export const pressShrink = { scale: 0.97 };

export const drawerSlide: Variants = {
    closed: { x: '-100%', transition: { duration: 0.22, ease: 'easeIn' } },
    open: { x: 0, transition: { duration: 0.28, ease: 'easeOut' } },
};
