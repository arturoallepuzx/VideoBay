import { Injectable, signal } from '@angular/core';
import { AccessibilitySettings } from '../auth/auth.models';

export type ThemeChoice = 'system' | 'light' | 'dark';
export type Density = 'compact' | 'normal' | 'comfy';
export type FontScale = 'sm' | 'md' | 'lg' | 'xl';
export type Contrast = 'normal' | 'high';
export type Motion = 'normal' | 'reduced';
export type SubtitleSize = 'sm' | 'md' | 'lg';

@Injectable({ providedIn: 'root' })
export class ThemeService {

  private readonly root = document.documentElement;
  private readonly systemLight = window.matchMedia('(prefers-color-scheme: light)');

  readonly theme = signal<ThemeChoice>(this.read('vb-theme', 'system') as ThemeChoice);
  readonly density = signal<Density>(this.read('vb-density', 'normal') as Density);
  readonly fontScale = signal<FontScale>(this.read('vb-fontscale', 'md') as FontScale);
  readonly contrast = signal<Contrast>(this.read('vb-contrast', 'normal') as Contrast);
  readonly motion = signal<Motion>(this.read('vb-motion', 'normal') as Motion);
  readonly bigSubtitles = signal<boolean>(this.read('vb-bigsubs', 'false') === 'true');
  readonly subtitleSize = signal<SubtitleSize>(this.read('vb-subsize', 'md') as SubtitleSize);
  readonly subtitleBackground = signal<boolean>(this.read('vb-subbg', 'true') === 'true');

  init(): void {
    this.applyTheme();
    this.root.setAttribute('data-density', this.density());
    this.root.setAttribute('data-fontscale', this.fontScale());
    this.root.setAttribute('data-contrast', this.contrast());
    this.root.setAttribute('data-motion', this.motion());

    this.systemLight.addEventListener('change', () => {
      if (this.theme() === 'system') {
        this.applyTheme();
      }
    });
  }

  resolvedTheme(): 'light' | 'dark' {
    const choice = this.theme();

    if (choice === 'system') {
      return this.systemLight.matches ? 'light' : 'dark';
    }

    return choice;
  }

  setTheme(choice: ThemeChoice): void {
    this.theme.set(choice);
    this.persist('vb-theme', choice);
    this.applyTheme();
  }

  toggleTheme(): void {
    this.setTheme(this.resolvedTheme() === 'dark' ? 'light' : 'dark');
  }

  setDensity(value: Density): void {
    this.density.set(value);
    this.persist('vb-density', value);
    this.root.setAttribute('data-density', value);
  }

  setFontScale(value: FontScale): void {
    this.fontScale.set(value);
    this.persist('vb-fontscale', value);
    this.root.setAttribute('data-fontscale', value);
  }

  setContrast(value: Contrast): void {
    this.contrast.set(value);
    this.persist('vb-contrast', value);
    this.root.setAttribute('data-contrast', value);
  }

  setMotion(value: Motion): void {
    this.motion.set(value);
    this.persist('vb-motion', value);
    this.root.setAttribute('data-motion', value);
  }

  setBigSubtitles(value: boolean): void {
    this.bigSubtitles.set(value);
    this.persist('vb-bigsubs', String(value));
  }

  setSubtitleSize(value: SubtitleSize): void {
    this.subtitleSize.set(value);
    this.persist('vb-subsize', value);
  }

  setSubtitleBackground(value: boolean): void {
    this.subtitleBackground.set(value);
    this.persist('vb-subbg', String(value));
  }

  applySettings(settings: AccessibilitySettings | null | undefined): void {
    if (!settings) {
      return;
    }

    if (settings.font_scale) {
      this.setFontScale(settings.font_scale);
    }
    if (settings.contrast) {
      this.setContrast(settings.contrast);
    }
    if (settings.motion) {
      this.setMotion(settings.motion);
    }
    if (settings.big_subtitles !== undefined) {
      this.setBigSubtitles(settings.big_subtitles);
    }
    if (settings.subtitle_size) {
      this.setSubtitleSize(settings.subtitle_size);
    }
    if (settings.subtitle_background !== undefined) {
      this.setSubtitleBackground(settings.subtitle_background);
    }
  }

  currentSettings(): AccessibilitySettings {
    return {
      font_scale: this.fontScale(),
      contrast: this.contrast(),
      motion: this.motion(),
      big_subtitles: this.bigSubtitles(),
      subtitle_size: this.subtitleSize(),
      subtitle_background: this.subtitleBackground(),
    };
  }

  private applyTheme(): void {
    this.root.setAttribute('data-theme', this.resolvedTheme());
  }

  private read(key: string, fallback: string): string {
    try {
      return localStorage.getItem(key) ?? fallback;
    } catch {
      return fallback;
    }
  }

  private persist(key: string, value: string): void {
    try {
      localStorage.setItem(key, value);
    } catch {
      return;
    }
  }
}
