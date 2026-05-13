import type { SVGProps } from 'react';

interface TemariBodyProps extends SVGProps<SVGSVGElement> {
    size?: number;
    color?: string;
}

// Hand-picked positions — biased away from the face zone so cross-stitches don't overlap features.
const STITCHES: ReadonlyArray<readonly [number, number]> = [
    [22, 28],
    [38, 22],
    [62, 22],
    [78, 28],
    [18, 50],
    [82, 50],
    [22, 72],
    [38, 78],
    [50, 82],
    [62, 78],
    [78, 72],
    [50, 28],
];

export default function TemariBody({ size = 144, color = 'currentColor', ...rest }: Readonly<TemariBodyProps>) {
    return (
        <svg viewBox="0 0 100 100" width={size} height={size} aria-hidden {...rest}>
            <path
                d="M 50 8 Q 60 50 50 92"
                fill="none"
                stroke={color}
                strokeWidth={0.9}
                strokeDasharray="2 2.5"
                opacity={0.45}
            />
            <path
                d="M 8 50 Q 50 60 92 50"
                fill="none"
                stroke={color}
                strokeWidth={0.9}
                strokeDasharray="2 2.5"
                opacity={0.45}
            />
            <g stroke={color} strokeWidth={0.9} opacity={0.28} strokeLinecap="round">
                {STITCHES.map(([cx, cy]) => (
                    <g key={`${cx}-${cy}`} transform={`translate(${cx} ${cy})`}>
                        <line x1={-1.4} y1={-1.4} x2={1.4} y2={1.4} />
                        <line x1={-1.4} y1={1.4} x2={1.4} y2={-1.4} />
                    </g>
                ))}
            </g>
            <path
                d="M 47 9 Q 50 3 53 9"
                fill="none"
                stroke={color}
                strokeWidth={1.4}
                strokeLinecap="round"
                opacity={0.7}
            />
        </svg>
    );
}
