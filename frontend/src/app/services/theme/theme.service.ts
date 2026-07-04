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
  readonly subtitleLanguage = signal<string>(this.read('vb-sublang', '') || 'es');
  readonly subtitleUuid = signal<string>(this.read('vb-subuuid', ''));
  readonly playerVolume = signal<number>(this.readVolume());
  readonly playerMuted = signal<boolean>(this.read('vb-muted', 'false') === 'true');

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

  setSubtitlePreference(language: string, uuid: string): void {
    this.subtitleLanguage.set(language);
    this.subtitleUuid.set(uuid);
    this.persist('vb-sublang', language);
    this.persist('vb-subuuid', uuid);
  }

  setPlayerVolume(value: number): void {
    this.playerVolume.set(value);
    this.persist('vb-volume', String(value));
  }

  setPlayerMuted(value: boolean): void {
    this.playerMuted.set(value);
    this.persist('vb-muted', String(value));
  }

  private readVolume(): number {
    const value = Number(this.read('vb-volume', '1'));

    return Number.isFinite(value) ? Math.min(1, Math.max(0, value)) : 1;
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
    if (settings.subtitle_language && settings.subtitle_uuid !== undefined) {
      this.setSubtitlePreference(settings.subtitle_language, settings.subtitle_uuid);
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
      subtitle_language: this.subtitleLanguage(),
      subtitle_uuid: this.subtitleUuid(),
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
